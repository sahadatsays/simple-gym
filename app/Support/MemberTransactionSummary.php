<?php

namespace App\Support;

use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;

readonly class MemberTransactionSummary
{
    public function __construct(
        public float $totalPaid,
        public float $totalAdmissionFee,
        public float $totalMembershipFee,
        public float $totalRenewalFee,
        public float $totalPosPaid,
        public float $totalDue,
        public int $paymentCount,
        public int $posOrderCount,
    ) {}

    public static function forMember(Member $member): self
    {
        $payments = Payment::query()
            ->where('member_id', $member->id)
            ->where('status', PaymentStatus::Completed)
            ->with('invoice')
            ->get();

        $membershipFeePayments = $payments->where('type', PaymentType::MembershipFee);

        $totalRenewalFee = Money::round((float) $membershipFeePayments
            ->filter(fn (Payment $payment): bool => $payment->invoice?->type === InvoiceType::Renewal)
            ->sum('amount'));

        $totalMembershipFee = Money::round((float) $membershipFeePayments
            ->filter(fn (Payment $payment): bool => $payment->invoice?->type !== InvoiceType::Renewal)
            ->sum('amount'));

        $totalAdmissionFee = Money::round((float) $payments
            ->where('type', PaymentType::AdmissionFee)
            ->sum('amount'));

        $totalPosPaid = Money::round((float) $payments
            ->where('type', PaymentType::PosSale)
            ->sum('amount'));

        $totalPaid = Money::round((float) $payments->sum('amount'));

        $invoices = Invoice::query()
            ->where('member_id', $member->id)
            ->with('payments')
            ->get();

        $totalDue = Money::round((float) $invoices->sum(
            fn (Invoice $invoice): float => $invoice->outstandingBalance()
        ));

        return new self(
            totalPaid: $totalPaid,
            totalAdmissionFee: $totalAdmissionFee,
            totalMembershipFee: $totalMembershipFee,
            totalRenewalFee: $totalRenewalFee,
            totalPosPaid: $totalPosPaid,
            totalDue: $totalDue,
            paymentCount: $payments->count(),
            posOrderCount: $invoices->where('type', InvoiceType::PosSale)->count(),
        );
    }
}
