<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'membership_plan_id' => ['nullable', 'integer', Rule::exists('membership_plans', 'id')],
            'status' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'export' => ['nullable', 'string', Rule::in(['pdf', 'excel', 'print'])],
        ];
    }

    /**
     * @return array{
     *     from_date: string,
     *     to_date: string,
     *     membership_plan_id: ?int,
     *     status: ?string,
     *     category: ?string,
     *     days: int
     * }
     */
    public function filters(ReportType $type): array
    {
        $validated = $this->validated();

        $fromDate = $validated['from_date'] ?? $this->defaultFromDate($type);
        $toDate = $validated['to_date'] ?? now()->toDateString();

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'membership_plan_id' => isset($validated['membership_plan_id'])
                ? (int) $validated['membership_plan_id']
                : null,
            'status' => $validated['status'] ?? null,
            'category' => $validated['category'] ?? null,
            'days' => (int) ($validated['days'] ?? 30),
        ];
    }

    public function exportFormat(): ?string
    {
        $export = $this->validated()['export'] ?? null;

        return filled($export) ? (string) $export : null;
    }

    private function defaultFromDate(ReportType $type): string
    {
        return match ($type) {
            ReportType::MonthlyCollection => now()->subMonths(11)->startOfMonth()->toDateString(),
            ReportType::UpcomingExpiry => now()->toDateString(),
            ReportType::Stock => now()->startOfMonth()->toDateString(),
            default => now()->startOfMonth()->toDateString(),
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('status') && $this->input('status') === '') {
            $this->merge(['status' => null]);
        }

        if ($this->filled('category') && $this->input('category') === '') {
            $this->merge(['category' => null]);
        }
    }
}
