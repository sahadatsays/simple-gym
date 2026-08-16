<?php

namespace App\Services;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Support\Money;
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
    public function createPosInvoice(?Member $member, array $lineItems, float $discountAmount = 0, ?Carbon $dueAt = null): Invoice
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
            'due_at' => $dueAt,
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

    public function syncPaymentStatus(Invoice $invoice): Invoice
    {
        $invoice->refresh();

        if ($invoice->status === InvoiceStatus::Void) {
            return $invoice;
        }

        $outstanding = $invoice->outstandingBalance();

        if ($outstanding <= 0) {
            return $this->invoices->update($invoice, [
                'status' => InvoiceStatus::Paid,
                'paid_at' => $invoice->paid_at ?? now(),
            ]);
        }

        if ($invoice->amountPaid() > 0) {
            return $this->invoices->update($invoice, [
                'status' => InvoiceStatus::Partial,
                'paid_at' => null,
            ]);
        }

        return $this->invoices->update($invoice, [
            'status' => InvoiceStatus::Unpaid,
            'paid_at' => null,
        ]);
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return $this->syncPaymentStatus($invoice);
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
     * @return array<int, array{
     *     id: int,
     *     invoice_number: string,
     *     member_name: ?string,
     *     member_code: ?string,
     *     plan_name: ?string,
     *     type_label: string,
     *     payment_type: string,
     *     subtotal: float,
     *     discount_amount: float,
     *     total: float,
     *     line_items: array<int, array<string, mixed>>
     * }>
     */
    public function unpaidInvoiceOptions(int $limit = 100): array
    {
        return Invoice::query()
            ->with(['member', 'membershipPlan', 'payments'])
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])
            ->latest('issued_at')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'member_name' => $invoice->member?->name,
                'member_code' => $invoice->member?->member_code,
                'plan_name' => $invoice->membershipPlan?->name,
                'type_label' => $invoice->type->label(),
                'payment_type' => $this->resolvePaymentType($invoice)->value,
                'subtotal' => (float) $invoice->subtotal,
                'discount_amount' => (float) $invoice->discount_amount,
                'total' => (float) $invoice->total,
                'amount_paid' => $invoice->amountPaid(),
                'outstanding_balance' => $invoice->outstandingBalance(),
                'line_items' => $invoice->line_items ?? [],
            ])
            ->values()
            ->all();
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
        $subtotal = Money::round(collect($lineItems)->sum('amount'));
        $discountAmount = Money::round(max(0, min($discountAmount, $subtotal)));

        return [
            'line_items' => $lineItems,
            'subtotal' => $subtotal,
            'total' => Money::round(max(0, $subtotal - $discountAmount)),
        ];
    }
}
