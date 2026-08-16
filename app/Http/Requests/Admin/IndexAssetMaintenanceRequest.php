<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetMaintenanceType;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AssetMaintenance::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'type' => ['nullable', 'string', Rule::enum(AssetMaintenanceType::class)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }
}
