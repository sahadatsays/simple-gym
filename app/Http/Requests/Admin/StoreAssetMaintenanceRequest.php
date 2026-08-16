<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AssetMaintenance::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'maintained_at' => ['required', 'date'],
            'type' => ['required', 'string', Rule::enum(AssetMaintenanceType::class)],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'next_maintenance_at' => ['nullable', 'date', 'after_or_equal:maintained_at'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'asset_status' => ['nullable', 'string', Rule::enum(AssetStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $asset = Asset::query()->find($this->input('asset_id'));

            if ($asset === null || ! $asset->isEligibleForMaintenance()) {
                $validator->errors()->add('asset_id', 'The selected asset is not available for maintenance.');
            }
        });
    }
}
