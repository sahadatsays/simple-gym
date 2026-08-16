<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Asset::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'status' => ['nullable', 'string', Rule::enum(AssetStatus::class)],
            'condition' => ['nullable', 'string', Rule::enum(AssetCondition::class)],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
