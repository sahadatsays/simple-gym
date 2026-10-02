<?php

namespace App\Http\Requests\Admin;

use App\Enums\LockerStatus;
use App\Models\Locker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Locker::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'locker_number' => ['required', 'string', 'max:50', Rule::unique('lockers', 'locker_number')],
            'location' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'monthly_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', 'string', Rule::enum(LockerStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'locker_number' => 'locker number',
            'category' => 'type / category',
            'monthly_fee' => 'monthly fee',
        ];
    }
}
