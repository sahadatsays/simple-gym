<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');
        $type = is_string($report) ? ReportType::tryFrom($report) : null;

        if ($type?->isAssetInvestmentReport()) {
            return $this->user()?->can('asset-investment-reports.view') ?? false;
        }

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
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'investment_category_id' => ['nullable', 'integer', Rule::exists('investment_categories', 'id')],
            'asset_category_id' => ['nullable', 'integer', Rule::exists('asset_categories', 'id')],
            'maintenance_type' => ['nullable', 'string', Rule::enum(AssetMaintenanceType::class)],
            'search' => ['nullable', 'string', 'max:255'],
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
     *     category_id: ?int,
     *     investment_category_id: ?int,
     *     asset_category_id: ?int,
     *     maintenance_type: ?string,
     *     search: ?string,
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
            'category_id' => isset($validated['category_id'])
                ? (int) $validated['category_id']
                : null,
            'investment_category_id' => isset($validated['investment_category_id'])
                ? (int) $validated['investment_category_id']
                : null,
            'asset_category_id' => isset($validated['asset_category_id'])
                ? (int) $validated['asset_category_id']
                : null,
            'maintenance_type' => $validated['maintenance_type'] ?? null,
            'search' => filled($validated['search'] ?? null) ? trim((string) $validated['search']) : null,
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
        foreach (['status', 'search', 'maintenance_type'] as $field) {
            if ($this->filled($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        foreach (['category_id', 'investment_category_id', 'asset_category_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        $report = $this->route('report');
        $type = is_string($report) ? ReportType::tryFrom($report) : null;

        if ($type === ReportType::Assets && filled($this->input('status'))) {
            $this->merge([
                'status' => AssetStatus::tryFrom((string) $this->input('status'))?->value,
            ]);
        }
    }
}
