<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AssetCategory $assetCategory */
        $assetCategory = $this->route('asset_category');

        return $this->user()?->can('update', $assetCategory) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var AssetCategory $assetCategory */
        $assetCategory = $this->route('asset_category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('asset_categories', 'name')->ignore($assetCategory->id)],
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
