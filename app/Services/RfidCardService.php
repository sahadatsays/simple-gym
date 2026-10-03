<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Enums\PaymentType;
use App\Enums\RfidCardStatus;
use App\Jobs\MemberAccessJob;
use App\Jobs\MemberAccessRevokeJob;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\RfidCardAssignment;
use App\Support\ActivityLogger;
use App\Support\Money;
use InvalidArgumentException;

class RfidCardService extends BaseService
{
    public function __construct(
        private RfidCardRepositoryInterface $rfidCards,
        private MemberRepositoryInterface $members,
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private GymSettingService $gymSettings,
        private ActivityLogger $activityLogger,
    ) {}

    public function register(string $cardNumber, ?int $createdBy): RfidCard
    {
        return $this->transaction(function () use ($cardNumber, $createdBy): RfidCard {
            $card = $this->rfidCards->create([
                'card_number' => $cardNumber,
                'card_fee' => 0,
                'deposit_amount' => 0,
                'status' => RfidCardStatus::Available,
                'created_by' => $createdBy,
            ]);

            $this->activityLogger->log('rfid_card.registered', $card, 'RFID card registered', [
                'card_number' => $card->card_number,
            ]);

            return $card;
        });
    }

    public function assign(RfidCard $card, Member $member, ?string $paymentMethod = null): RfidCard
    {
        return $this->transaction(function () use ($card, $member, $paymentMethod): RfidCard {
            $card = $this->lockCard($card);

            $this->assertCardCanBeAssigned($card);

            $charges = $this->issueCharges();
            $invoice = $this->collectCharge($member, $charges, $paymentMethod);

            $this->closeAssignedCardsForMember($member);

            return $this->activateAssignment($card, $member, $charges, $invoice, 'rfid_card.assigned', 'RFID card assigned to member');
        });
    }

    public function issue(Member $member, string $cardNumber, ?string $paymentMethod = null, ?int $createdBy = null): RfidCard
    {
        return $this->transaction(function () use ($member, $cardNumber, $paymentMethod, $createdBy): RfidCard {
            $member = Member::query()->whereKey($member->id)->lockForUpdate()->first();

            if ($member === null) {
                throw new InvalidArgumentException('The selected member was not found.');
            }

            if ($member->activeRfidCard()->exists()) {
                throw new InvalidArgumentException('This member already has an active card. Replace it instead.');
            }

            $card = $this->rfidCards->findByCardNumber($cardNumber);

            if ($card === null) {
                $card = $this->rfidCards->create([
                    'card_number' => $cardNumber,
                    'card_fee' => 0,
                    'deposit_amount' => 0,
                    'status' => RfidCardStatus::Available,
                    'created_by' => $createdBy,
                ]);
            }

            return $this->assign($card, $member, $paymentMethod);
        });
    }

    public function replace(Member $member, string $cardNumber, ?string $paymentMethod = null, ?int $createdBy = null): RfidCard
    {
        return $this->transaction(function () use ($member, $cardNumber, $paymentMethod, $createdBy): RfidCard {
            $card = $this->rfidCards->findByCardNumber($cardNumber);

            if ($card === null) {
                $card = $this->rfidCards->create([
                    'card_number' => $cardNumber,
                    'card_fee' => 0,
                    'deposit_amount' => 0,
                    'status' => RfidCardStatus::Available,
                    'created_by' => $createdBy,
                ]);
            }

            $card = $this->lockCard($card);

            if ($card->member_id === $member->id && $card->isActive()) {
                throw new InvalidArgumentException('This card is already assigned to the member.');
            }

            $this->assertCardCanBeAssigned($card);

            $charges = $this->replacementCharges();
            $invoice = $this->collectCharge($member, $charges, $paymentMethod);

            $this->closeAssignedCardsForMember($member);

            return $this->activateAssignment($card, $member, $charges, $invoice, 'rfid_card.replaced', 'Member RFID card replaced');
        });
    }

    public function returnCard(RfidCard $card): RfidCard
    {
        return $this->transaction(function () use ($card): RfidCard {
            $card = $this->lockCard($card);

            if (! $card->isActive()) {
                throw new InvalidArgumentException('Only an assigned card can be returned.');
            }

            $member = $card->member;
            $returnedCard = $this->closeAssignment($card, RfidCardStatus::Returned);

            if ($member !== null) {
                $this->syncMemberRfidCard($member, null);
                $this->queueDeviceAccessRevoke($member, $returnedCard);
            }

            $this->activityLogger->log('rfid_card.returned', $returnedCard, 'RFID card returned', [
                'card_number' => $returnedCard->card_number,
            ]);

            return $returnedCard->load('member');
        });
    }

    public function markLost(RfidCard $card): RfidCard
    {
        return $this->transaction(function () use ($card): RfidCard {
            $card = $this->lockCard($card);

            if ($card->status === RfidCardStatus::Lost) {
                throw new InvalidArgumentException('This card is already lost.');
            }

            if ($card->status === RfidCardStatus::Blocked) {
                throw new InvalidArgumentException('Unblock the card before marking it lost.');
            }

            $member = $card->member;
            $lostCard = $this->closeAssignment($card, RfidCardStatus::Lost);

            if ($member !== null) {
                $this->syncMemberRfidCard($member, null);
                $this->queueDeviceAccessRevoke($member, $lostCard);
            }

            $this->activityLogger->log('rfid_card.lost', $lostCard, 'RFID card marked lost', [
                'card_number' => $lostCard->card_number,
            ]);

            return $lostCard->load('member');
        });
    }

    public function disable(RfidCard $card): RfidCard
    {
        if ($card->status === RfidCardStatus::Blocked) {
            throw new InvalidArgumentException('This card is already disabled.');
        }

        return $this->transaction(function () use ($card): RfidCard {
            $card = $this->lockCard($card);
            $member = $card->member;

            $this->setOpenAssignmentStatus($card, RfidCardStatus::Blocked);

            $disabledCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Blocked,
            ]);

            if ($member !== null) {
                $this->syncMemberRfidCard($member, null);
            }

            $this->activityLogger->log('rfid_card.disabled', $disabledCard, 'RFID card disabled', [
                'card_number' => $disabledCard->card_number,
            ]);

            if ($member !== null) {
                $this->queueDeviceAccessRevoke($member, $card);
            }

            return $disabledCard->load('member');
        });
    }

    public function enable(RfidCard $card): RfidCard
    {
        if (! $card->isDisabled()) {
            throw new InvalidArgumentException('Only disabled cards can be enabled.');
        }

        $member = $card->member;

        if ($member === null) {
            throw new InvalidArgumentException('This card is not assigned to a member.');
        }

        if (! $member->isActive()) {
            throw new InvalidArgumentException('Cannot enable card for an expired member. Renew membership first.');
        }

        if ($member->activeRfidCard !== null) {
            throw new InvalidArgumentException('This member already has an active RFID card.');
        }

        return $this->transaction(function () use ($card, $member): RfidCard {
            $card = $this->lockCard($card);

            $this->setOpenAssignmentStatus($card, RfidCardStatus::Assigned);

            $enabledCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Assigned,
            ]);

            $this->syncMemberRfidCard($member, $enabledCard->card_number);

            $this->activityLogger->log('rfid_card.enabled', $enabledCard, 'RFID card enabled', [
                'member_code' => $member->member_code,
            ]);

            $this->queueDeviceAccessSync($member);

            return $enabledCard->load('member');
        });
    }

    public function disableAllForMember(Member $member): void
    {
        $this->transaction(function () use ($member): void {
            $this->rfidCards->disableActiveCardsForMember($member);
            $this->syncMemberRfidCard($member, null);
        });
    }

    public function reactivateLatestCardForMember(Member $member): ?RfidCard
    {
        $member->loadMissing('activeRfidCard');

        if ($member->activeRfidCard !== null) {
            return $member->activeRfidCard;
        }

        $card = RfidCard::query()
            ->where('member_id', $member->id)
            ->where('status', RfidCardStatus::Blocked)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->first();

        if ($card === null) {
            return null;
        }

        return $this->transaction(function () use ($member, $card): RfidCard {
            $card = $this->lockCard($card);

            $this->setOpenAssignmentStatus($card, RfidCardStatus::Assigned);

            $reactivatedCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Assigned,
            ]);

            $this->syncMemberRfidCard($member, $reactivatedCard->card_number);

            $this->activityLogger->log('rfid_card.reactivated', $reactivatedCard, 'RFID card reactivated after renewal', [
                'member_code' => $member->member_code,
            ]);

            return $reactivatedCard;
        });
    }

    private function assertCardCanBeAssigned(RfidCard $card): void
    {
        if ($card->status === RfidCardStatus::Lost || $card->status === RfidCardStatus::Blocked) {
            throw new InvalidArgumentException('Lost or blocked cards cannot be assigned.');
        }

        if (! $card->isAssignable()) {
            throw new InvalidArgumentException('This card cannot be assigned.');
        }
    }

    /**
     * @return array{card_fee: float, deposit_amount: float, replacement_fee: float}
     */
    private function issueCharges(): array
    {
        $settings = $this->gymSettings->get();

        return [
            'card_fee' => Money::round((float) $settings->rfid_card_fee),
            'deposit_amount' => Money::round((float) $settings->rfid_card_deposit),
            'replacement_fee' => 0.0,
        ];
    }

    /**
     * @return array{card_fee: float, deposit_amount: float, replacement_fee: float}
     */
    private function replacementCharges(): array
    {
        $settings = $this->gymSettings->get();

        return [
            'card_fee' => 0.0,
            'deposit_amount' => 0.0,
            'replacement_fee' => Money::round((float) $settings->rfid_replacement_card_fee),
        ];
    }

    /**
     * @param  array{card_fee: float, deposit_amount: float, replacement_fee: float}  $charges
     */
    private function collectCharge(Member $member, array $charges, ?string $paymentMethod): ?Invoice
    {
        $invoice = $this->invoiceService->createRfidInvoice(
            $member,
            $charges['card_fee'],
            $charges['deposit_amount'],
            $charges['replacement_fee'],
        );

        if ($invoice === null) {
            return null;
        }

        if ($paymentMethod === null || $paymentMethod === '') {
            throw new InvalidArgumentException('Choose a payment method for the card charge.');
        }

        $this->paymentService->settleInvoice(
            invoice: $invoice,
            member: $member,
            amountPaid: (float) $invoice->total,
            paymentMethod: $paymentMethod,
            type: PaymentType::RfidCard,
        );

        return $invoice->fresh();
    }

    /**
     * @param  array{card_fee: float, deposit_amount: float, replacement_fee: float}  $charges
     */
    private function activateAssignment(RfidCard $card, Member $member, array $charges, ?Invoice $invoice, string $event, string $message): RfidCard
    {
        $recordedFee = Money::greaterThan($charges['replacement_fee'], 0)
            ? $charges['replacement_fee']
            : $charges['card_fee'];
        $recordedDeposit = Money::greaterThan($charges['replacement_fee'], 0)
            ? 0.0
            : $charges['deposit_amount'];

        $assignedCard = $this->rfidCards->update($card, [
            'status' => RfidCardStatus::Assigned,
            'member_id' => $member->id,
            'assigned_at' => now(),
            'card_fee' => $recordedFee,
            'deposit_amount' => $recordedDeposit,
        ]);

        RfidCardAssignment::query()->create([
            'member_id' => $member->id,
            'rfid_card_id' => $assignedCard->id,
            'issue_date' => now(),
            'return_date' => null,
            'card_fee' => $recordedFee,
            'deposit_amount' => $recordedDeposit,
            'status' => RfidCardStatus::Assigned,
            'invoice_id' => $invoice?->id,
            'open_card_id' => $assignedCard->id,
        ]);

        $this->syncMemberRfidCard($member, $assignedCard->card_number);

        $this->activityLogger->log($event, $assignedCard, $message, [
            'member_code' => $member->member_code,
        ]);

        $this->queueDeviceAccessSync($member);

        return $assignedCard->load('member');
    }

    private function closeAssignedCardsForMember(Member $member): void
    {
        $cards = RfidCard::query()
            ->where('member_id', $member->id)
            ->where('status', RfidCardStatus::Assigned)
            ->lockForUpdate()
            ->get();

        foreach ($cards as $card) {
            $this->closeAssignment($card, RfidCardStatus::Returned);
        }
    }

    private function closeAssignment(RfidCard $card, RfidCardStatus $status): RfidCard
    {
        $assignment = RfidCardAssignment::query()
            ->where('rfid_card_id', $card->id)
            ->whereNull('return_date')
            ->lockForUpdate()
            ->first();

        if ($assignment !== null) {
            $assignment->update([
                'status' => $status,
                'return_date' => now(),
                'open_card_id' => null,
            ]);
        }

        return $this->rfidCards->update($card, [
            'status' => $status,
            'member_id' => null,
        ]);
    }

    private function setOpenAssignmentStatus(RfidCard $card, RfidCardStatus $status): void
    {
        RfidCardAssignment::query()
            ->where('rfid_card_id', $card->id)
            ->whereNull('return_date')
            ->lockForUpdate()
            ->update([
                'status' => $status,
            ]);
    }

    private function lockCard(RfidCard $card): RfidCard
    {
        $locked = RfidCard::query()->whereKey($card->id)->lockForUpdate()->first();

        if ($locked === null) {
            throw new InvalidArgumentException('The selected RFID card was not found.');
        }

        return $locked;
    }

    private function syncMemberRfidCard(Member $member, ?string $cardNumber): void
    {
        $this->members->update($member, [
            'rfid_card' => $cardNumber,
        ]);
    }

    private function queueDeviceAccessSync(Member $member): void
    {
        MemberAccessJob::dispatch($member->id)->afterCommit();
    }

    private function queueDeviceAccessRevoke(Member $member, RfidCard $card): void
    {
        MemberAccessRevokeJob::dispatch($member->id, $card->id)->afterCommit();
    }
}
