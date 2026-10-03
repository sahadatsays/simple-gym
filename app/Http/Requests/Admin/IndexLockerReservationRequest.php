<?php

namespace App\Http\Requests\Admin;

use App\Enums\LockerReservationStatus;
use App\Models\LockerReservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLockerReservationRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::enum(LockerReservationStatus::class)],
        ];
    }
}
