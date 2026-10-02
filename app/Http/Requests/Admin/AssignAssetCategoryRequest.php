<?php

namespace App\Http\Requests\Admin;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AssetCategory|null $assetCategory */
        $assetCategory = $this->route('asset_category');

        return $assetCategory !== null
            && ($this->user()?->can('view', $assetCategory) ?? false)
            && ($this->user()?->can('assets.edit') ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var AssetCategory $assetCategory */
                $assetCategory = $this->route('asset_category');

                if (! $assetCategory->is_active) {
                    $validator->errors()->add('asset_id', 'Activate this category before assigning assets.');

                    return;
                }

                $asset = Asset::query()->find($this->integer('asset_id'));

                if ($asset !== null && $asset->asset_category_id === $assetCategory->id) {
                    $validator->errors()->add('asset_id', 'This asset is already assigned to this category.');
                }
            },
        ];
    }
}
