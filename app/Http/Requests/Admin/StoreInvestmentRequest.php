<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Investment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Investment::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'invested_at' => ['required', 'date'],
            'investment_category_id' => ['required', 'integer', 'exists:investment_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
