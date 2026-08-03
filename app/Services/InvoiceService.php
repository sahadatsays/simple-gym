<?php

namespace App\Services;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class InvoiceService extends BaseService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
    ) {}

    /**
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculateRegistrationCharges(MembershipPlan $plan, float $discountAmount = 0): array
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

        return $this->buildChargeSummary($lineItems, $discountAmount);
    }

    /**
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculateRenewalCharges(MembershipPlan $plan, float $discountAmount = 0): array
    {
        $lineItems = [
            [
                'description' => 'Membership Renewal Fee',
                'amount' => (float) $plan->membership_fee,
            ],
        ];

        return $this->buildChargeSummary($lineItems, $discountAmount);
    }

    /**
     * @param  array<int, array{description: string, amount: float}>  $lineItems
     * @return array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}
     */
    public function calculatePosCharges(array $lineItems, float $discountAmount = 0): array
    {
        return $this->buildChargeSummary($lineItems, $discountAmount);
    }

    public function createForMember(Member $member, MembershipPlan $plan, float $discountAmount = 0): Invoice
    {
        $charges = $this->calculateRegistrationCharges($plan, $discountAmount);

        return $this->createInvoice($member, $plan, InvoiceType::Registration, $charges, $discountAmount);
    }

    public function createRenewalForMember(Member $member, MembershipPlan $plan, float $discountAmount = 0): Invoice
    {
        $charges = $this->calculateRenewalCharges($plan, $discountAmount);

        return $this->createInvoice($member, $plan, InvoiceType::Renewal, $charges, $discountAmount);
    }

    /**
     * @param  array<int, array{description: string, amount: float}>  $lineItems
     */
    public function createPosInvoice(?Member $member, array $lineItems, float $discountAmount = 0): Invoice
    {
        $charges = $this->calculatePosCharges($lineItems, $discountAmount);

        return $this->invoices->create([
            'member_id' => $member?->id,
            'membership_plan_id' => null,
            'type' => InvoiceType::PosSale,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'subtotal' => $charges['subtotal'],
            'discount_amount' => $discountAmount,
            'total' => $charges['total'],
            'status' => InvoiceStatus::Unpaid,
            'line_items' => $charges['line_items'],
            'issued_at' => now(),
        ]);
    }

    public function applyDiscount(Invoice $invoice, float $discountAmount): Invoice
    {
        if ($invoice->isPaid()) {
            throw new InvalidArgumentException('Cannot apply a discount to a paid invoice.');
        }

        if ($discountAmount < 0) {
            throw new InvalidArgumentException('Discount amount cannot be negative.');
        }

        if ($discountAmount > (float) $invoice->subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed the invoice subtotal.');
        }

        return $this->invoices->update($invoice, [
            'discount_amount' => $discountAmount,
            'total' => max(0, (float) $invoice->subtotal - $discountAmount),
        ]);
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

    public function resolvePaymentType(Invoice $invoice): PaymentType
    {
        if ($invoice->isPosSale()) {
            return PaymentType::PosSale;
        }

        $descriptions = collect($invoice->line_items ?? [])
            ->pluck('description')
            ->map(fn (string $description): string => strtolower($description));

        if ($descriptions->contains(fn (string $description): bool => str_contains($description, 'admission'))) {
            return PaymentType::AdmissionFee;
        }

        return PaymentType::MembershipFee;
    }

    /**
     * @param  array{line_items: array<int, array{description: string, amount: float}>, subtotal: float, total: float}  $charges
     */
    private function createInvoice(
        Member $member,
        MembershipPlan $plan,
        InvoiceType $type,
        array $charges,
        float $discountAmount = 0,
    ): Invoice {
        return $this->invoices->create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'type' => $type,
            'invoice_number' => $this->invoices->nextInvoiceNumber(),
            'subtotal' => $charges['subtotal'],
            'discount_amount' => $discountAmount,
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
    private function buildChargeSummary(array $lineItems, float $discountAmount = 0): array
    {
        $subtotal = collect($lineItems)->sum('amount');
        $discountAmount = max(0, min($discountAmount, $subtotal));

        return [
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discountAmount),
        ];
    }
}
