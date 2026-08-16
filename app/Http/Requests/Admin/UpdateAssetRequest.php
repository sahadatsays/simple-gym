<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Asset $asset */
        $asset = $this->route('asset');

        return $this->user()?->can('update', $asset) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_category_id' => ['required', 'integer', 'exists:asset_categories,id'],
            'purchased_at' => ['required', 'date'],
            'purchase_price' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'current_value' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'lte:purchase_price'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'string', Rule::enum(AssetCondition::class)],
            'status' => ['required', 'string', Rule::enum(AssetStatus::class)],
            'warranty_expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('current_value') && $this->filled('purchase_price')
                && (float) $this->input('current_value') > (float) $this->input('purchase_price')) {
                $validator->errors()->add('current_value', 'Current value cannot exceed the purchase price.');
            }
        });
    }
}
