<?php

namespace App\Services;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Support\ActivityLogger;

class PaymentService extends BaseService
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private InvoiceService $invoiceService,
        private ActivityLogger $activityLogger,
    ) {}

    public function receive(
        Invoice $invoice,
        Member $member,
        float $amountReceived,
        string $paymentMethod,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        if ($amountReceived < (float) $invoice->total) {
            throw PaymentFailedException::insufficientAmount((float) $invoice->total, $amountReceived);
        }

        if ($amountReceived <= 0) {
            throw PaymentFailedException::declined();
        }

        $payment = $this->payments->create([
            'member_id' => $member->id,
            'invoice_id' => $invoice->id,
            'type' => PaymentType::Membership,
            'status' => PaymentStatus::Completed,
            'amount' => $invoice->total,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'reference' => $reference,
            'receipt_number' => $this->payments->nextReceiptNumber(),
            'notes' => $notes,
        ]);

        $this->invoiceService->markPaid($invoice);

        $this->activityLogger->log('payment.received', $payment, 'Membership payment received', [
            'invoice_number' => $invoice->invoice_number,
            'receipt_number' => $payment->receipt_number,
            'amount' => $payment->amount,
        ]);

        return $payment;
    }
}
