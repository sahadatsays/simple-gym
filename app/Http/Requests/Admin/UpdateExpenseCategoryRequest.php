<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ExpenseCategory $expenseCategory */
        $expenseCategory = $this->route('expense_category');

        return $this->user()?->can('update', $expenseCategory) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ExpenseCategory $expenseCategory */
        $expenseCategory = $this->route('expense_category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id)],
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
