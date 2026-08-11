<?php

namespace App\Http\Requests\Admin;

use App\Models\AttendanceLog;
use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AttendanceLog::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sn' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }
}
