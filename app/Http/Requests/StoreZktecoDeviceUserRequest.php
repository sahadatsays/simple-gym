<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZktecoDeviceUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uid' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'privilege' => ['nullable', 'integer', 'min:0', 'max:14'],
            'card_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
