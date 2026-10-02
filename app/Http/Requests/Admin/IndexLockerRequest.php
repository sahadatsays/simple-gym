<?php

namespace App\Http\Requests\Admin;

use App\Enums\LockerStatus;
use App\Models\Locker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Locker::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(LockerStatus::class)],
        ];
    }
}
