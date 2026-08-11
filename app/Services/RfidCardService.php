<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Enums\RfidCardStatus;
use App\Jobs\MemberAccessJob;
use App\Models\Member;
use App\Models\RfidCard;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class RfidCardService extends BaseService
{
    public function __construct(
        private RfidCardRepositoryInterface $rfidCards,
        private MemberRepositoryInterface $members,
        private ActivityLogger $activityLogger,
    ) {}

    public function register(string $cardNumber): RfidCard
    {
        return $this->transaction(function () use ($cardNumber): RfidCard {
            $card = $this->rfidCards->create([
                'card_number' => $cardNumber,
                'status' => RfidCardStatus::Unassigned,
            ]);

            $this->activityLogger->log('rfid_card.registered', $card, 'RFID card registered', [
                'card_number' => $card->card_number,
            ]);

            return $card;
        });
    }

    public function assign(RfidCard $card, Member $member): RfidCard
    {
        if (! $card->isAssignable()) {
            throw new InvalidArgumentException('Only unassigned cards can be assigned to a member.');
        }

        return $this->transaction(function () use ($card, $member): RfidCard {
            $this->rfidCards->disableActiveCardsForMember($member);

            $assignedCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Active,
                'member_id' => $member->id,
                'assigned_at' => now(),
            ]);

            $this->syncMemberRfidCard($member, $assignedCard->card_number);

            $this->activityLogger->log('rfid_card.assigned', $assignedCard, 'RFID card assigned to member', [
                'member_code' => $member->member_code,
            ]);

            $this->queueDeviceAccessSync($member);

            return $assignedCard->load('member');
        });
    }

    public function replace(Member $member, string $cardNumber): RfidCard
    {
        return $this->transaction(function () use ($member, $cardNumber): RfidCard {
            $this->rfidCards->disableActiveCardsForMember($member);

            $card = $this->rfidCards->findByCardNumber($cardNumber);

            if ($card === null) {
                $card = $this->rfidCards->create([
                    'card_number' => $cardNumber,
                    'status' => RfidCardStatus::Unassigned,
                ]);
            }

            if (! $card->isAssignable()) {
                throw new InvalidArgumentException('This card cannot be assigned because it is not unassigned.');
            }

            $assignedCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Active,
                'member_id' => $member->id,
                'assigned_at' => now(),
            ]);

            $this->syncMemberRfidCard($member, $assignedCard->card_number);

            $this->activityLogger->log('rfid_card.replaced', $assignedCard, 'Member RFID card replaced', [
                'member_code' => $member->member_code,
            ]);

            $this->queueDeviceAccessSync($member);

            return $assignedCard->load('member');
        });
    }

    public function disable(RfidCard $card): RfidCard
    {
        if ($card->status === RfidCardStatus::Disabled) {
            throw new InvalidArgumentException('This card is already disabled.');
        }

        return $this->transaction(function () use ($card): RfidCard {
            $member = $card->member;

            $disabledCard = $this->rfidCards->update($card, [
                'status' => RfidCardStatus::Disabled,
            ]);

            if ($member !== null) {
                $this->syncMemberRfidCard($member, null);
            }

            $this->activityLogger->log('rfid_card.disabled', $disabledCard, 'RFID card disabled', [
                'card_number' => $disabledCard->card_number,
            ]);

            return $disabledCard->load('member');
        });
    }

    public function disableAllForMember(Member $member): void
    {
        $this->transaction(function () use ($member): void {
            $this->rfidCards->disableActiveCardsForMember($member);
            $this->syncMemberRfidCard($member, null);
        });
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
}
