<?php

namespace App\Services;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;

class InvoiceService extends BaseService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
    ) {}

    /**
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculateCharges(MembershipPlan $plan): array
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

        $total = collect($lineItems)->sum('amount');

        return [
            'line_items' => $lineItems,
            'subtotal' => $total,
            'total' => $total,
        ];
    }

    public function createForMember(Member $member, MembershipPlan $plan): Invoice
    {
        $charges = $this->calculateCharges($plan);

        return $this->invoices->create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'subtotal' => $charges['subtotal'],
            'total' => $charges['total'],
            'status' => InvoiceStatus::Unpaid,
            'line_items' => $charges['line_items'],
            'issued_at' => now(),
        ]);
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return $this->invoices->update($invoice, [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
