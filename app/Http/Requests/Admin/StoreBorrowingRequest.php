<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Borrowing::class) ?? false;
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
}
