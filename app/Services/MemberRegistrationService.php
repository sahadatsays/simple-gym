<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Data\MemberRegistrationResult;
use App\Enums\MemberStatus;
use App\Enums\PaymentType;
use App\Enums\PlanStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\RfidCard;
use App\Support\ActivityLogger;
use App\Support\MemberPhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class MemberRegistrationService extends BaseService
{
    public function __construct(
        private MemberRepositoryInterface $members,
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private RfidCardService $rfidCardService,
        private ActivityLogger $activityLogger,
        private MemberPhotoStorage $photoStorage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, ?UploadedFile $photo = null): MemberRegistrationResult
    {
        return $this->transaction(function () use ($data, $photo): MemberRegistrationResult {
            $this->assertNoDuplicateRegistration($data['phone'], $data['email'] ?? null);

            $plan = MembershipPlan::query()
                ->whereKey($data['membership_plan_id'])
                ->where('status', PlanStatus::Active)
                ->first();

            if ($plan === null) {
                throw new InvalidArgumentException('Selected membership plan is not available.');
            }

            $memberAttributes = $this->buildMemberAttributes($data);
            unset($memberAttributes['photo'], $memberAttributes['remove_photo']);

            if ($photo !== null) {
                $memberAttributes['photo_path'] = $this->storePhoto($photo);
            }

            $member = $this->members->create($memberAttributes);

            $invoice = $this->invoiceService->createForMember($member, $plan);

            $payment = $this->paymentService->receive(
                invoice: $invoice,
                member: $member,
                amountPaid: (float) $data['amount_received'],
                paymentMethod: $data['payment_method'],
                type: PaymentType::MembershipFee,
                discountAmount: (float) ($data['discount_amount'] ?? 0),
                reference: $data['payment_reference'] ?? null,
            );

            $member = $this->activateMembership($member, $plan, $data['joined_at']);

            if (! empty($data['rfid_card_id'])) {
                /** @var RfidCard $card */
                $card = RfidCard::query()->findOrFail((int) $data['rfid_card_id']);

                if (! $card->isAssignable()) {
                    throw new InvalidArgumentException('Selected RFID card is not available for assignment.');
                }

                $this->rfidCardService->assign($card, $member);
            }

            $this->activityLogger->log('member.registered', $member, 'Member registered with payment', [
                'member_code' => $member->member_code,
                'invoice_number' => $invoice->invoice_number,
                'receipt_number' => $payment->receipt_number,
            ]);

            return new MemberRegistrationResult(
                member: $member->load(['membershipPlan', 'activeRfidCard']),
                invoice: $invoice->load('membershipPlan'),
                payment: $payment->load('invoice'),
            );
        });
    }

    private function assertNoDuplicateRegistration(string $phone, ?string $email): void
    {
        if ($this->members->findByPhone($phone) !== null) {
            throw new InvalidArgumentException('A member with this phone number is already registered.');
        }

        if ($email !== null && $email !== '') {
            $existing = Member::query()
                ->where('email', $email)
                ->exists();

            if ($existing) {
                throw new InvalidArgumentException('A member with this email is already registered.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildMemberAttributes(array $data): array
    {
        return [
            'member_code' => $this->members->nextMemberCode(),
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'membership_plan_id' => $data['membership_plan_id'],
            'joined_at' => $data['joined_at'],
            'membership_expires_at' => null,
            'status' => MemberStatus::Pending,
        ];
    }

    private function activateMembership(Member $member, MembershipPlan $plan, string $joinedAt): Member
    {
        $expiresAt = Carbon::parse($joinedAt)
            ->addDays($plan->duration_days)
            ->toDateString();

        return $this->members->update($member, [
            'status' => MemberStatus::Active,
            'membership_expires_at' => $expiresAt,
        ]);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $this->photoStorage->store($photo);
    }
}
