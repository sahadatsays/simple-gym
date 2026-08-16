<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetDisposalType;
use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AssetDisposal::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'disposed_at' => ['required', 'date'],
            'disposal_type' => ['required', 'string', Rule::enum(AssetDisposalType::class)],
            'sale_amount' => [
                Rule::requiredIf(fn (): bool => $this->input('disposal_type') === AssetDisposalType::Sold->value),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
            ],
            'buyer' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $asset = Asset::query()->with('disposal')->find($this->input('asset_id'));

            if ($asset === null || ! $asset->isEligibleForDisposal()) {
                $validator->errors()->add('asset_id', 'This asset cannot be disposed.');
            }
        });
    }
}
