<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')],
            'type' => ['nullable', 'string', Rule::enum(PaymentType::class)],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateInvoicePayment($validator);
        });
    }

    private function validateInvoicePayment(Validator $validator): void
    {
        $invoice = Invoice::query()->find($this->integer('invoice_id'));

        if ($invoice === null) {
            return;
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            $validator->errors()->add('invoice_id', 'This invoice has already been paid.');

            return;
        }

        $discountAmount = (float) ($this->input('discount_amount') ?? 0);
        $amountPaid = (float) $this->input('amount_paid');
        $invoiceTotal = max(0, (float) $invoice->subtotal - $discountAmount);

        if ($discountAmount > (float) $invoice->subtotal) {
            $validator->errors()->add('discount_amount', 'Discount cannot exceed the invoice subtotal.');
        }

        if ($amountPaid > $invoiceTotal) {
            $validator->errors()->add('amount_paid', 'Paid amount cannot exceed the invoice total.');
        }

        if ($amountPaid < $invoiceTotal) {
            $validator->errors()->add('amount_paid', 'Paid amount must cover the full invoice total.');
        }
    }
}
