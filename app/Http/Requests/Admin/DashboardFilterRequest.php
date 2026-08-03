<?php

namespace App\Http\Requests\Admin;

use App\Enums\DashboardDatePreset;
use App\Support\DashboardDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.view') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'string', Rule::enum(DashboardDatePreset::class)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ];
    }

    public function dateRange(): DashboardDateRange
    {
        return DashboardDateRange::fromInput($this->validated());
    }
}
