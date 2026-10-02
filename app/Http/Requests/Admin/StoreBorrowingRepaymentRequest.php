<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBorrowingRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrowings.edit') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'borrowing_id' => ['required', 'integer', 'exists:borrowings,id'],
            'repayment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.gt' => 'Repayment amount must be greater than zero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $borrowing = Borrowing::query()->find($this->integer('borrowing_id'));

            if (! $borrowing instanceof Borrowing || ! $borrowing->acceptsRepayment()) {
                $validator->errors()->add('borrowing_id', 'This borrowing cannot receive a repayment.');

                return;
            }

            if ($this->date('repayment_date')?->lt($borrowing->borrowing_date->copy()->startOfDay())) {
                $validator->errors()->add('repayment_date', 'Repayment date cannot be before the borrowing date.');
            }

            if (Money::greaterThan((float) $this->input('amount'), $borrowing->remaining_amount)) {
                $validator->errors()->add('amount', 'Repayment cannot exceed the remaining amount.');
            }
        });
    }
}
