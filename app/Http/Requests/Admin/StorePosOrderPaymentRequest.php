<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePosOrderPaymentRequest extends FormRequest
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
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $invoice = $this->route('invoice');

            if (! $invoice instanceof Invoice || ! $invoice->isOpen()) {
                $validator->errors()->add('amount_paid', 'This order cannot accept payments.');

                return;
            }

            $amountPaid = Money::round((float) $this->input('amount_paid'));
            $outstanding = Money::round($invoice->outstandingBalance());

            if (Money::greaterThan($amountPaid, $outstanding)) {
                $validator->errors()->add('amount_paid', "Payment cannot exceed the outstanding balance of {$outstanding}.");
            }
        });
    }
}
