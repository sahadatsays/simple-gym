<?php

namespace App\Services;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Support\Carbon;

class InvoiceService extends BaseService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
    ) {}

    /**
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculateRegistrationCharges(MembershipPlan $plan): array
    {
        $lineItems = [];

        if ((float) $plan->admission_fee > 0) {
            $lineItems[] = [
                'description' => 'Admission Fee',
                'amount' => (float) $plan->admission_fee,
            ];
        }

        $lineItems[] = [
            'description' => 'Membership Fee',
            'amount' => (float) $plan->membership_fee,
        ];

        return $this->buildChargeSummary($lineItems);
    }

    /**
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculateRenewalCharges(MembershipPlan $plan): array
    {
        $lineItems = [
            [
                'description' => 'Membership Renewal Fee',
                'amount' => (float) $plan->membership_fee,
            ],
        ];

        return $this->buildChargeSummary($lineItems);
    }

    public function createForMember(Member $member, MembershipPlan $plan): Invoice
    {
        $charges = $this->calculateRegistrationCharges($plan);

        return $this->createInvoice($member, $plan, InvoiceType::Registration, $charges);
    }

    public function createRenewalForMember(Member $member, MembershipPlan $plan): Invoice
    {
        $charges = $this->calculateRenewalCharges($plan);

        return $this->createInvoice($member, $plan, InvoiceType::Renewal, $charges);
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return $this->invoices->update($invoice, [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function calculateRenewedExpiry(Member $member, MembershipPlan $plan): Carbon
    {
        if ($member->isActive()) {
            $baseDate = $member->membership_expires_at ?? today();

            return $baseDate->copy()->addDays($plan->duration_days);
        }

        return today()->addDays($plan->duration_days);
    }

    /**
     * @param  array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}  $charges
     */
    private function createInvoice(
        Member $member,
        MembershipPlan $plan,
        InvoiceType $type,
        array $charges,
    ): Invoice {
        return $this->invoices->create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'type' => $type,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'subtotal' => $charges['subtotal'],
            'total' => $charges['total'],
            'status' => InvoiceStatus::Unpaid,
            'line_items' => $charges['line_items'],
            'issued_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array{description: string, amount: float}>  $lineItems
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    private function buildChargeSummary(array $lineItems): array
    {
        $total = collect($lineItems)->sum('amount');

        return [
            'line_items' => $lineItems,
            'subtotal' => $total,
            'total' => $total,
        ];
    }
}
