<?php

namespace App\Http\Requests\Admin;

use App\Models\RfidCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RfidCard::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'max:50', Rule::unique('rfid_cards', 'card_number')],
        ];
    }
}
