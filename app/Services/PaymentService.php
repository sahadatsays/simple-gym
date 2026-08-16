<?php

namespace App\Services;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\PaymentFailedException;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Support\ActivityLogger;
use App\Support\Money;
use Illuminate\Support\Carbon;

class PaymentService extends BaseService
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private InvoiceService $invoiceService,
        private ProductSaleService $productSaleService,
        private ActivityLogger $activityLogger,
    ) {}

    public function receive(
        Invoice $invoice,
        ?Member $member,
        float $amountPaid,
        PaymentMethod|string $paymentMethod,
        ?PaymentType $type = null,
        float $discountAmount = 0,
        ?string $reference = null,
        ?string $notes = null,
        bool $requireFullPayment = true,
    ): Payment {
        return $this->receiveForInvoice($invoice, [
            'member_id' => $member?->id ?? $invoice->member_id,
            'type' => $type ?? $this->invoiceService->resolvePaymentType($invoice),
            'amount_paid' => $amountPaid,
            'payment_method' => $paymentMethod instanceof PaymentMethod ? $paymentMethod->value : $paymentMethod,
            'discount_amount' => $discountAmount,
            'reference' => $reference,
            'notes' => $notes,
            'require_full_payment' => $requireFullPayment,
        ]);
    }

    /**
     * @param  array{
     *     member_id?: int|null,
     *     type: PaymentType,
     *     amount_paid: float,
     *     payment_method: string,
     *     discount_amount?: float,
     *     reference?: string|null,
     *     notes?: string|null,
     *     require_full_payment?: bool,
     *     line_items?: array<int, array{product_id?: int|null, description: string, amount: float, quantity?: int, unit_price?: float}>
     * }  $data
     */
    public function receiveForInvoice(Invoice $invoice, array $data): Payment
    {
        return $this->transaction(function () use ($invoice, $data): Payment {
            $invoice->refresh();

            if ($invoice->isPaid()) {
                throw PaymentFailedException::alreadyPaid();
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0);

            if ($discountAmount > 0) {
                $invoice = $this->invoiceService->applyDiscount($invoice, $discountAmount);
            }

            $amountPaid = Money::round((float) $data['amount_paid']);
            $outstanding = Money::round($invoice->outstandingBalance());

            if ($amountPaid <= 0) {
                throw PaymentFailedException::declined();
            }

            if (Money::greaterThan($amountPaid, $outstanding)) {
                throw PaymentFailedException::exceedsInvoiceAmount($outstanding, $amountPaid);
            }

            $requireFullPayment = $data['require_full_payment'] ?? true;

            if ($requireFullPayment && Money::lessThan($amountPaid, $outstanding)) {
                throw PaymentFailedException::insufficientAmount($outstanding, $amountPaid);
            }

            $payment = $this->payments->create([
                'member_id' => $data['member_id'] ?? $invoice->member_id,
                'invoice_id' => $invoice->id,
                'type' => $data['type'],
                'status' => PaymentStatus::Completed,
                'amount' => $amountPaid,
                'discount_amount' => $discountAmount,
                'paid_at' => now(),
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'receipt_number' => $this->payments->nextReceiptNumber(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($invoice->isPosSale() && ! empty($data['line_items'] ?? null)) {
                $this->productSaleService->recordFromPosPayment(
                    $payment,
                    $invoice,
                    $data['line_items'],
                );
            }

            $this->invoiceService->syncPaymentStatus($invoice->fresh(['payments']));

            $this->activityLogger->log('payment.received', $payment, 'Payment received', [
                'invoice_number' => $invoice->invoice_number,
                'receipt_number' => $payment->receipt_number,
                'amount' => $payment->amount,
                'type' => $payment->type->value,
            ]);

            return $payment;
        });
    }

    /**
     * @param  array<int, array{product_id?: int|null, description: string, amount: float, quantity?: int, unit_price?: float}>  $lineItems
     * @param  array{
     *     payment_method?: string,
     *     amount_paid?: float,
     *     discount_amount?: float,
     *     reference?: string|null,
     *     notes?: string|null,
     *     due_at?: Carbon|string|null
     * }  $paymentData
     * @return array{invoice: Invoice, payment: ?Payment}
     */
    public function createPosOrder(?Member $member, array $lineItems, array $paymentData): array
    {
        return $this->transaction(function () use ($member, $lineItems, $paymentData): array {
            $discountAmount = (float) ($paymentData['discount_amount'] ?? 0);
            $dueAt = isset($paymentData['due_at']) ? Carbon::parse($paymentData['due_at']) : null;

            $invoice = $this->invoiceService->createPosInvoice($member, $lineItems, $discountAmount, $dueAt);

            $this->productSaleService->recordFromPosOrder($invoice, $lineItems);

            $amountPaid = Money::round((float) ($paymentData['amount_paid'] ?? 0));
            $payment = null;

            if ($amountPaid > 0) {
                $payment = $this->receiveForInvoice($invoice, [
                    'member_id' => $member?->id,
                    'type' => PaymentType::PosSale,
                    'amount_paid' => $amountPaid,
                    'payment_method' => $paymentData['payment_method'] ?? PaymentMethod::Cash->value,
                    'discount_amount' => 0,
                    'reference' => $paymentData['reference'] ?? null,
                    'notes' => $paymentData['notes'] ?? null,
                    'require_full_payment' => false,
                ]);
            }

            return [
                'invoice' => $invoice->fresh(['payments', 'member', 'productSales']),
                'payment' => $payment,
            ];
        });
    }

    /**
     * @param  array{
     *     type: PaymentType,
     *     amount_paid: float,
     *     payment_method: string,
     *     discount_amount?: float,
     *     reference?: string|null,
     *     notes?: string|null
     * }  $paymentData
     *
     * @deprecated Use createPosOrder() instead.
     */
    public function receivePosSale(?Member $member, array $lineItems, array $paymentData): Payment
    {
        $result = $this->createPosOrder($member, $lineItems, $paymentData);

        if ($result['payment'] === null) {
            throw PaymentFailedException::declined();
        }

        return $result['payment'];
    }
}
