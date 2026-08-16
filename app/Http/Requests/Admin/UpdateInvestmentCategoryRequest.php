<?php

namespace App\Http\Requests\Admin;

use App\Models\InvestmentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvestmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var InvestmentCategory $investmentCategory */
        $investmentCategory = $this->route('investment_category');

        return $this->user()?->can('update', $investmentCategory) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var InvestmentCategory $investmentCategory */
        $investmentCategory = $this->route('investment_category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('investment_categories', 'name')->ignore($investmentCategory->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
