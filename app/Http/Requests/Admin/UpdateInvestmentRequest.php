<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Investment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $investment = $this->route('investment');

        return $investment instanceof Investment
            && ($this->user()?->can('update', $investment) ?? false);
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
            'remove_attachment' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_attachment')) {
            $this->merge([
                'remove_attachment' => $this->boolean('remove_attachment'),
            ]);
        }
    }
}
