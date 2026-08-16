<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AssetMaintenance $assetMaintenance */
        $assetMaintenance = $this->route('asset_maintenance');

        return $this->user()?->can('update', $assetMaintenance) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'maintained_at' => ['required', 'date'],
            'type' => ['required', 'string', Rule::enum(AssetMaintenanceType::class)],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'next_maintenance_at' => ['nullable', 'date', 'after_or_equal:maintained_at'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'remove_attachment' => ['sometimes', 'boolean'],
            'asset_status' => ['nullable', 'string', Rule::in(array_keys(AssetStatus::operationalOptions()))],
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
