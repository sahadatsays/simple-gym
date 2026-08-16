<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetDisposalType;
use App\Models\AssetDisposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssetDisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AssetDisposal::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'disposal_type' => ['nullable', 'string', Rule::enum(AssetDisposalType::class)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }
}
