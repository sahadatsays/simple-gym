<?php

namespace App\Http\Requests\Admin;

use App\Support\CurrencyRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', Rule::in(CurrencyRegistry::codes())],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i', 'after:opening_time'],
            'is_open' => ['nullable', 'boolean'],
        ];
    }
}
