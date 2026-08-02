<?php

namespace App\Http\Requests\Admin;

use App\Enums\RfidCardStatus;
use App\Models\RfidCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', RfidCard::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(RfidCardStatus::class)],
        ];
    }
}
