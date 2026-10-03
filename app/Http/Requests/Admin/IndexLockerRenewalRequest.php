<?php

namespace App\Http\Requests\Admin;

use App\Models\LockerReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLockerRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', LockerReservation::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
