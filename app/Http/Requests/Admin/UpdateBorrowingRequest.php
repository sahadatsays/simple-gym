<?php

namespace App\Http\Requests\Admin;

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing
            && ($this->user()?->can('update', $borrowing) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'lender_name' => ['required', 'string', 'max:255'],
            'lender_phone' => ['nullable', 'string', 'max:20'],
            'borrowing_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date', 'after_or_equal:borrowing_date'],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::enum(BorrowingStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.gt' => 'Amount must be greater than zero.',
            'due_date.after_or_equal' => 'Due date cannot be before the borrowing date.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $borrowing = $this->route('borrowing');

                if (! $borrowing instanceof Borrowing || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $repaid = (float) $borrowing->repayments()->sum('amount');

                if (Money::greaterThan($repaid, (float) $this->input('amount'))) {
                    $validator->errors()->add(
                        'amount',
                        'Amount cannot be less than the amount already returned.',
                    );
                }
            },
        ];
    }
}
