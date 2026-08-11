<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Data\MemberRenewalResult;
use App\Enums\MemberStatus;
use App\Enums\PaymentType;
use App\Enums\PlanStatus;
use App\Jobs\MemberAccessJob;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\MembershipRenewal;
use App\Support\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class MembershipRenewalService extends BaseService
{
    public function __construct(
        private MemberRepositoryInterface $members,
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private GymSettingService $gymSettings,
        private RfidCardService $rfidCardService,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function renew(Member $member, array $data): MemberRenewalResult
    {
        return $this->transaction(function () use ($member, $data): MemberRenewalResult {
            if (! $member->isRenewable()) {
                throw new InvalidArgumentException('Pending members must complete registration before renewal.');
            }

            $plan = MembershipPlan::query()
                ->whereKey($data['membership_plan_id'])
                ->where('status', PlanStatus::Active)
                ->first();

            if ($plan === null) {
                throw new InvalidArgumentException('Selected membership plan is not available.');
            }

            $previousExpiresAt = $member->membership_expires_at;
            $newExpiresAt = $this->invoiceService->calculateRenewedExpiry($member, $plan);

            $invoice = $this->invoiceService->createRenewalForMember($member, $plan);

            $payment = $this->paymentService->receive(
                invoice: $invoice,
                member: $member,
                amountPaid: (float) $data['amount_received'],
                paymentMethod: $data['payment_method'],
                type: PaymentType::MembershipFee,
                discountAmount: (float) ($data['discount_amount'] ?? 0),
                reference: $data['payment_reference'] ?? null,
            );

            $member = $this->members->update($member, [
                'membership_plan_id' => $plan->id,
                'membership_expires_at' => $newExpiresAt->toDateString(),
                'status' => MemberStatus::Active,
            ]);

            $renewal = MembershipRenewal::query()->create([
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'invoice_id' => $invoice->id,
                'previous_expires_at' => $previousExpiresAt?->toDateString(),
                'new_expires_at' => $newExpiresAt->toDateString(),
                'renewed_at' => now(),
            ]);

            $this->activityLogger->log('member.renewed', $member, 'Membership renewed with payment', [
                'member_code' => $member->member_code,
                'invoice_number' => $invoice->invoice_number,
                'receipt_number' => $payment->receipt_number,
                'previous_expires_at' => $previousExpiresAt?->toDateString(),
                'new_expires_at' => $newExpiresAt->toDateString(),
            ]);

            $this->rfidCardService->reactivateLatestCardForMember($member);
            MemberAccessJob::dispatch($member->id)->afterCommit();

            return new MemberRenewalResult(
                member: $member->load('membershipPlan'),
                invoice: $invoice->load('membershipPlan'),
                payment: $payment->load('invoice'),
                renewal: $renewal->load(['membershipPlan', 'invoice']),
            );
        });
    }

    public function previewExpiry(Member $member, MembershipPlan $plan): Carbon
    {
        return $this->invoiceService->calculateRenewedExpiry($member, $plan);
    }

    /**
     * @param  array{search?: string|null, per_page?: int|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<Member>
     */
    public function paginateReview(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? config('gym.pagination.per_page'));

        return $this->members->paginateRenewalReview(
            $filters,
            $this->gymSettings->membershipReminderDays(),
            $perPage,
        );
    }
}
