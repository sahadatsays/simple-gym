<?php

namespace App\Services;

use App\Models\GymSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\InvoiceQrCode;
use App\Support\Money;

class InvoiceDocumentService
{
    public function __construct(
        private GymSettingService $gymSettings,
        private InvoiceQrCode $qrCode,
    ) {}

    /**
     * @return array{
     *     invoice: Invoice,
     *     payment: ?Payment,
     *     gym: GymSetting,
     *     currency: string,
     *     summary: array{
     *         subtotal: float,
     *         discount: float,
     *         total: float,
     *         amount_paid: float,
     *         outstanding_balance: float
     *     },
     *     payment_summary: array<int, array{
     *         receipt_number: string,
     *         paid_at: string,
     *         method: string,
     *         amount: float,
     *         reference: ?string
     *     }>,
     *     qr_code_svg: string,
     *     verification_url: string
     * }
     */
    public function build(Invoice $invoice): array
    {
        $invoice->load([
            'member',
            'membershipPlan',
            'payments',
            'membershipRenewal',
        ]);

        $gym = $this->gymSettings->get();
        $payment = $invoice->payment;
        $amountPaid = $invoice->amountPaid();
        $outstandingBalance = $invoice->outstandingBalance();

        return [
            'invoice' => $invoice,
            'payment' => $payment,
            'gym' => $gym,
            'currency' => $gym->currency,
            'summary' => [
                'subtotal' => Money::round((float) $invoice->subtotal),
                'discount' => Money::round((float) $invoice->discount_amount),
                'total' => Money::round((float) $invoice->total),
                'amount_paid' => $amountPaid,
                'outstanding_balance' => $outstandingBalance,
            ],
            'payment_summary' => $this->paymentSummary($invoice),
            'qr_code_svg' => $this->qrCode->forInvoice($invoice),
            'verification_url' => route('admin.invoices.show', $invoice),
        ];
    }

    /**
     * @return array<int, array{
     *     receipt_number: string,
     *     paid_at: string,
     *     method: string,
     *     amount: float,
     *     reference: ?string
     * }>
     */
    private function paymentSummary(Invoice $invoice): array
    {
        return $invoice->payments
            ->map(fn (Payment $payment): array => [
                'receipt_number' => $payment->receipt_number,
                'paid_at' => $payment->paid_at->format('M j, Y g:i A'),
                'method' => $payment->payment_method->label(),
                'amount' => Money::round((float) $payment->amount),
                'reference' => $payment->reference,
            ])
            ->values()
            ->all();
    }
}
