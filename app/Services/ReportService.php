<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ProductStatus;
use App\Enums\ReportType;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     membership_plan_id?: int|null,
     *     status?: string|null,
     *     category?: string|null,
     *     days?: int
     * }  $filters
     * @return array{
     *     summary: array<string, float|int|string|null>,
     *     rows: Collection<int, array<string, mixed>>|LengthAwarePaginator<int, array<string, mixed>>,
     *     columns: array<int, array{key: string, label: string, align?: string}>
     * }
     */
    public function build(ReportType $type, array $filters, ?int $perPage = null): array
    {
        return match ($type) {
            ReportType::DailyCollection => $this->dailyCollection($filters),
            ReportType::MonthlyCollection => $this->monthlyCollection($filters),
            ReportType::Membership => $this->membershipReport($filters, $perPage),
            ReportType::ExpiredMembers => $this->expiredMembers($filters, $perPage),
            ReportType::UpcomingExpiry => $this->upcomingExpiry($filters, $perPage),
            ReportType::PosSales => $this->posSales($filters, $perPage),
            ReportType::ProductSales => $this->productSales($filters, $perPage),
            ReportType::Stock => $this->stockReport($filters, $perPage),
            ReportType::Investments => $this->investmentReport($filters, $perPage),
            ReportType::Assets => $this->assetReport($filters, $perPage),
            ReportType::AssetMaintenance => $this->assetMaintenanceReport($filters, $perPage),
            ReportType::AssetValueSummary => $this->assetValueSummary($filters),
            ReportType::Expenses => $this->expenseReport($filters, $perPage),
        };
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     investment_category_id?: int|null,
     *     search?: string|null
     * }  $filters
     */
    private function investmentReport(array $filters, ?int $perPage): array
    {
        $query = Investment::query()
            ->with('category')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('investment_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($filters['investment_category_id'] ?? null), fn (Builder $query) => $query->where('investment_category_id', $filters['investment_category_id']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('invested_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('invested_at', '<=', $filters['to_date']))
            ->latest('invested_at')
            ->latest('id');

        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->selectRaw('COUNT(*) as investment_count')
            ->first();

        $rows = $this->paginateOrGet($query, $perPage, fn (Investment $investment): array => [
            'date' => $investment->invested_at->format('M j, Y'),
            'investment_number' => $investment->investment_number,
            'category' => $investment->category?->name ?? '—',
            'amount' => Money::round((float) $investment->amount),
            'payment_method' => $investment->payment_method->label(),
            'description' => $investment->description ?: '—',
        ]);

        return [
            'summary' => [
                'total_investment' => Money::round((float) ($aggregate->total_amount ?? 0)),
                'investment_count' => (int) ($aggregate->investment_count ?? 0),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'investment_number', 'label' => 'Investment No'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'end'],
                ['key' => 'payment_method', 'label' => 'Payment Method'],
                ['key' => 'description', 'label' => 'Description'],
            ],
        ];
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     asset_category_id?: int|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     */
    private function assetReport(array $filters, ?int $perPage): array
    {
        $query = $this->assetReportQuery($filters)->latest('purchased_at')->latest('id');

        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(purchase_price), 0) as total_purchase_value')
            ->selectRaw('COALESCE(SUM(current_value), 0) as total_current_value')
            ->selectRaw('COUNT(*) as asset_count')
            ->first();

        $rows = $this->paginateOrGet($query, $perPage, fn (Asset $asset): array => [
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'category' => $asset->category?->name ?? '—',
            'purchase_date' => $asset->purchased_at->format('M j, Y'),
            'purchase_price' => Money::round((float) $asset->purchase_price),
            'current_value' => Money::round((float) ($asset->current_value ?? 0)),
            'condition' => $asset->condition?->label() ?? '—',
            'status' => $asset->status?->label() ?? '—',
            'location' => $asset->location ?: '—',
        ]);

        return [
            'summary' => [
                'asset_count' => (int) ($aggregate->asset_count ?? 0),
                'total_purchase_value' => Money::round((float) ($aggregate->total_purchase_value ?? 0)),
                'total_current_value' => Money::round((float) ($aggregate->total_current_value ?? 0)),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'asset_code', 'label' => 'Asset Code'],
                ['key' => 'name', 'label' => 'Asset'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'purchase_date', 'label' => 'Purchase Date'],
                ['key' => 'purchase_price', 'label' => 'Purchase Price', 'align' => 'end'],
                ['key' => 'current_value', 'label' => 'Current Value', 'align' => 'end'],
                ['key' => 'condition', 'label' => 'Condition'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'location', 'label' => 'Location'],
            ],
        ];
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     asset_category_id?: int|null,
     *     maintenance_type?: string|null,
     *     search?: string|null
     * }  $filters
     */
    private function assetMaintenanceReport(array $filters, ?int $perPage): array
    {
        $query = AssetMaintenance::query()
            ->with(['asset.category'])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('description', 'like', "%{$search}%")
                        ->orWhere('service_provider', 'like', "%{$search}%")
                        ->orWhereHas('asset', function (Builder $assetQuery) use ($search): void {
                            $assetQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['asset_category_id'] ?? null), fn (Builder $query) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('asset_category_id', $filters['asset_category_id'])
            ))
            ->when(filled($filters['maintenance_type'] ?? null), fn (Builder $query) => $query->where('type', $filters['maintenance_type']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('maintained_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('maintained_at', '<=', $filters['to_date']))
            ->latest('maintained_at')
            ->latest('id');

        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(cost), 0) as total_maintenance_cost')
            ->selectRaw('COUNT(*) as maintenance_count')
            ->first();

        $rows = $this->paginateOrGet($query, $perPage, fn (AssetMaintenance $maintenance): array => [
            'asset' => $maintenance->asset
                ? "{$maintenance->asset->name} ({$maintenance->asset->asset_code})"
                : '—',
            'date' => $maintenance->maintained_at->format('M j, Y'),
            'maintenance_type' => $maintenance->type->label(),
            'cost' => $maintenance->cost !== null ? Money::round((float) $maintenance->cost) : null,
            'service_provider' => $maintenance->service_provider ?: '—',
            'next_maintenance_date' => $maintenance->next_maintenance_at?->format('M j, Y') ?? '—',
        ]);

        return [
            'summary' => [
                'maintenance_count' => (int) ($aggregate->maintenance_count ?? 0),
                'total_maintenance_cost' => Money::round((float) ($aggregate->total_maintenance_cost ?? 0)),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'asset', 'label' => 'Asset'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'maintenance_type', 'label' => 'Maintenance Type'],
                ['key' => 'cost', 'label' => 'Cost', 'align' => 'end'],
                ['key' => 'service_provider', 'label' => 'Service Provider'],
                ['key' => 'next_maintenance_date', 'label' => 'Next Maintenance Date'],
            ],
        ];
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     asset_category_id?: int|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     */
    private function assetValueSummary(array $filters): array
    {
        $purchaseQuery = $this->assetReportQuery($filters);

        $currentValueQuery = Asset::query()
            ->when(filled($filters['asset_category_id'] ?? null), fn (Builder $query) => $query->where('asset_category_id', $filters['asset_category_id']))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->where('status', AssetStatus::Active);

        $maintenanceQuery = AssetMaintenance::query()
            ->when(filled($filters['asset_category_id'] ?? null), fn (Builder $query) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('asset_category_id', $filters['asset_category_id'])
            ))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->whereHas('asset', function (Builder $assetQuery) use ($search): void {
                    $assetQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('maintained_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('maintained_at', '<=', $filters['to_date']));

        return [
            'summary' => [
                'total_purchase_value' => Money::round((float) $purchaseQuery->sum('purchase_price')),
                'current_asset_value' => Money::round((float) $currentValueQuery->sum(DB::raw('COALESCE(current_value, 0)'))),
                'total_maintenance_cost' => Money::round((float) $maintenanceQuery->sum(DB::raw('COALESCE(cost, 0)'))),
            ],
            'rows' => collect(),
            'columns' => [],
        ];
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     expense_category_id?: int|null,
     *     payment_method?: string|null,
     *     status?: string|null
     * }  $filters
     */
    private function expenseReport(array $filters, ?int $perPage): array
    {
        $query = $this->expenseReportQuery($filters)
            ->with(['category:id,name', 'creator:id,name'])
            ->latest('expensed_at')
            ->latest('id');

        $aggregate = (clone $this->expenseReportQuery($filters))
            ->selectRaw('COALESCE(SUM(amount), 0) as total_expense')
            ->selectRaw('COUNT(*) as expense_count')
            ->first();

        $categorySummary = (clone $this->expenseReportQuery($filters))
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->select([
                'expense_categories.name as category',
            ])
            ->selectRaw('COALESCE(SUM(expenses.amount), 0) as total_amount')
            ->selectRaw('COUNT(expenses.id) as expense_count')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row): array => [
                'category' => (string) $row->category,
                'total_amount' => Money::round((float) $row->total_amount),
                'expense_count' => (int) $row->expense_count,
            ])
            ->values();

        $rows = $this->paginateOrGet($query, $perPage, fn (Expense $expense): array => [
            'expense_number' => $expense->expense_number,
            'date' => $expense->expensed_at->format('M j, Y'),
            'category' => $expense->category?->name ?? '—',
            'amount' => Money::round((float) $expense->amount),
            'payment_method' => $expense->payment_method->label(),
            'paid_to' => $expense->paid_to ?: '—',
            'description' => $expense->description ?: '—',
            'created_by' => $expense->creator?->name ?? '—',
        ]);

        return [
            'summary' => [
                'total_expense' => Money::round((float) ($aggregate->total_expense ?? 0)),
                'expense_count' => (int) ($aggregate->expense_count ?? 0),
            ],
            'category_summary' => $categorySummary,
            'rows' => $rows,
            'columns' => [
                ['key' => 'expense_number', 'label' => 'Expense No'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'end'],
                ['key' => 'payment_method', 'label' => 'Payment Method'],
                ['key' => 'paid_to', 'label' => 'Paid To'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'created_by', 'label' => 'Created By'],
            ],
        ];
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     expense_category_id?: int|null,
     *     payment_method?: string|null,
     *     status?: string|null
     * }  $filters
     * @return Builder<Expense>
     */
    private function expenseReportQuery(array $filters): Builder
    {
        return Expense::query()
            ->when(filled($filters['expense_category_id'] ?? null), fn (Builder $query) => $query->where('expense_category_id', $filters['expense_category_id']))
            ->when(filled($filters['payment_method'] ?? null), fn (Builder $query) => $query->where('payment_method', $filters['payment_method']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('expensed_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('expensed_at', '<=', $filters['to_date']));
    }

    /**
     * @param  array{
     *     from_date: string,
     *     to_date: string,
     *     asset_category_id?: int|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<Asset>
     */
    private function assetReportQuery(array $filters): Builder
    {
        return Asset::query()
            ->with('category')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['asset_category_id'] ?? null), fn (Builder $query) => $query->where('asset_category_id', $filters['asset_category_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('purchased_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('purchased_at', '<=', $filters['to_date']));
    }

    /**
     * @param  array{from_date: string, to_date: string}  $filters
     * @return array{summary: array<string, float|int>, rows: Collection<int, array<string, mixed>>, columns: array<int, array{key: string, label: string, align?: string}>}
     */
    private function dailyCollection(array $filters): array
    {
        $payments = $this->paymentQuery($filters)->get();

        $rows = $payments
            ->groupBy(fn (Payment $payment): string => $payment->paid_at->toDateString())
            ->map(function (Collection $group, string $date): array {
                $admission = $this->sumByType($group, PaymentType::AdmissionFee);
                $membership = $this->sumByType($group, PaymentType::MembershipFee);
                $pos = $this->sumByType($group, PaymentType::PosSale);

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('M j, Y'),
                    'admission_fee' => $admission,
                    'membership_fee' => $membership,
                    'pos_sale' => $pos,
                    'total' => Money::round($admission + $membership + $pos),
                    'count' => $group->count(),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $summary = $this->paymentSummary($payments);

        return [
            'summary' => $summary,
            'rows' => $rows,
            'columns' => $this->collectionColumns(),
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string}  $filters
     * @return array{summary: array<string, float|int>, rows: Collection<int, array<string, mixed>>, columns: array<int, array{key: string, label: string, align?: string}>}
     */
    private function monthlyCollection(array $filters): array
    {
        $payments = $this->paymentQuery($filters)->get();

        $rows = $payments
            ->groupBy(fn (Payment $payment): string => $payment->paid_at->format('Y-m'))
            ->map(function (Collection $group, string $month): array {
                $admission = $this->sumByType($group, PaymentType::AdmissionFee);
                $membership = $this->sumByType($group, PaymentType::MembershipFee);
                $pos = $this->sumByType($group, PaymentType::PosSale);

                return [
                    'month' => $month,
                    'label' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'admission_fee' => $admission,
                    'membership_fee' => $membership,
                    'pos_sale' => $pos,
                    'total' => Money::round($admission + $membership + $pos),
                    'count' => $group->count(),
                ];
            })
            ->sortKeysDesc()
            ->values();

        return [
            'summary' => $this->paymentSummary($payments),
            'rows' => $rows,
            'columns' => $this->collectionColumns(isMonthly: true),
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string, membership_plan_id?: int|null, status?: string|null}  $filters
     */
    private function membershipReport(array $filters, ?int $perPage): array
    {
        $query = Member::query()
            ->with('membershipPlan')
            ->when(filled($filters['membership_plan_id'] ?? null), fn (Builder $query) => $query->where('membership_plan_id', $filters['membership_plan_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('joined_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('joined_at', '<=', $filters['to_date']))
            ->orderBy('joined_at', 'desc');

        $members = $this->paginateOrGet($query, $perPage, fn (Member $member): array => $this->formatMemberRow($member));

        $allMembers = Member::query()
            ->when(filled($filters['membership_plan_id'] ?? null), fn (Builder $query) => $query->where('membership_plan_id', $filters['membership_plan_id']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('joined_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('joined_at', '<=', $filters['to_date']))
            ->get();

        return [
            'summary' => [
                'total_members' => $allMembers->count(),
                'active_members' => $allMembers->filter(fn (Member $member): bool => $member->isActive())->count(),
                'expired_members' => $allMembers->filter(fn (Member $member): bool => ! $member->isActive() && $member->status === MemberStatus::Expired)->count(),
                'pending_members' => $allMembers->where('status', MemberStatus::Pending)->count(),
            ],
            'rows' => $members,
            'columns' => [
                ['key' => 'member_code', 'label' => 'Code'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'plan', 'label' => 'Plan'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'joined_at', 'label' => 'Joined'],
                ['key' => 'expires_at', 'label' => 'Expires'],
            ],
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string, membership_plan_id?: int|null}  $filters
     */
    private function expiredMembers(array $filters, ?int $perPage): array
    {
        $query = Member::query()
            ->with('membershipPlan')
            ->expired()
            ->when(filled($filters['membership_plan_id'] ?? null), fn (Builder $query) => $query->where('membership_plan_id', $filters['membership_plan_id']))
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('membership_expires_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('membership_expires_at', '<=', $filters['to_date']))
            ->orderBy('membership_expires_at', 'desc');

        $countQuery = clone $query;

        $rows = $this->paginateOrGet($query, $perPage, fn (Member $member): array => $this->formatMemberRow($member));

        return [
            'summary' => [
                'expired_count' => $countQuery->count(),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'member_code', 'label' => 'Code'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'plan', 'label' => 'Plan'],
                ['key' => 'expires_at', 'label' => 'Expired On'],
                ['key' => 'days_expired', 'label' => 'Days Expired'],
            ],
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string, membership_plan_id?: int|null, days?: int}  $filters
     */
    private function upcomingExpiry(array $filters, ?int $perPage): array
    {
        $toDate = $filters['to_date'] ?? now()->addDays($filters['days'] ?? 30)->toDateString();

        $query = Member::query()
            ->with('membershipPlan')
            ->where('status', MemberStatus::Active)
            ->whereNotNull('membership_expires_at')
            ->whereDate('membership_expires_at', '>=', $filters['from_date'])
            ->whereDate('membership_expires_at', '<=', $toDate)
            ->when(filled($filters['membership_plan_id'] ?? null), fn (Builder $query) => $query->where('membership_plan_id', $filters['membership_plan_id']))
            ->orderBy('membership_expires_at');

        $countQuery = clone $query;

        $rows = $this->paginateOrGet($query, $perPage, function (Member $member): array {
            $row = $this->formatMemberRow($member);
            $row['days_remaining'] = max(0, today()->diffInDays($member->membership_expires_at, false));

            return $row;
        });

        return [
            'summary' => [
                'expiring_count' => $countQuery->count(),
                'window_start' => Carbon::parse($filters['from_date'])->format('M j, Y'),
                'window_end' => Carbon::parse($toDate)->format('M j, Y'),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'member_code', 'label' => 'Code'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'plan', 'label' => 'Plan'],
                ['key' => 'expires_at', 'label' => 'Expires'],
                ['key' => 'days_remaining', 'label' => 'Days Left'],
            ],
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string}  $filters
     */
    private function posSales(array $filters, ?int $perPage): array
    {
        $query = $this->paymentQuery($filters)
            ->where('type', PaymentType::PosSale)
            ->with(['member', 'invoice']);

        $allPayments = (clone $query)->get();

        $payments = $this->paginateOrGet($query, $perPage, fn (Payment $payment): array => [
            'receipt' => $payment->receipt_number,
            'date' => $payment->paid_at->format('M j, Y g:i A'),
            'member' => $payment->member?->name ?? 'Walk-in',
            'method' => $payment->payment_method->label(),
            'discount' => Money::round((float) $payment->discount_amount),
            'amount' => Money::round((float) $payment->amount),
        ]);

        return [
            'summary' => [
                'total_sales' => Money::round((float) $allPayments->sum('amount')),
                'transaction_count' => $allPayments->count(),
                'total_discount' => Money::round((float) $allPayments->sum('discount_amount')),
            ],
            'rows' => $payments,
            'columns' => [
                ['key' => 'receipt', 'label' => 'Receipt'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'member', 'label' => 'Customer'],
                ['key' => 'method', 'label' => 'Method'],
                ['key' => 'discount', 'label' => 'Discount', 'align' => 'end'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'end'],
            ],
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string, category?: string|null}  $filters
     */
    private function productSales(array $filters, ?int $perPage): array
    {
        $query = ProductSale::query()
            ->with(['product', 'member', 'payment'])
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('sold_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('sold_at', '<=', $filters['to_date']))
            ->when(filled($filters['category_id'] ?? null), fn (Builder $query) => $query->whereHas(
                'product',
                fn (Builder $productQuery) => $productQuery->where('category_id', $filters['category_id'])
            ))
            ->latest('sold_at');

        $rows = $this->paginateOrGet($query, $perPage, fn (ProductSale $sale): array => [
            'date' => $sale->sold_at->format('M j, Y'),
            'product' => $sale->product?->name ?? '—',
            'sku' => $sale->product?->sku ?? '—',
            'member' => $sale->member?->name ?? 'Walk-in',
            'quantity' => $sale->quantity,
            'unit_price' => Money::round((float) $sale->unit_price),
            'line_total' => Money::round((float) $sale->line_total),
            'profit' => Money::round($sale->grossProfit()),
        ]);

        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_units')
            ->selectRaw('COALESCE(SUM(line_total), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as sales_count')
            ->first();

        $totalRevenue = Money::round((float) ($aggregate->total_revenue ?? 0));
        $totalCost = Money::round((float) ($aggregate->total_cost ?? 0));

        return [
            'summary' => [
                'units_sold' => (int) ($aggregate->total_units ?? 0),
                'sales_count' => (int) ($aggregate->sales_count ?? 0),
                'total_revenue' => $totalRevenue,
                'gross_profit' => Money::round($totalRevenue - $totalCost),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'product', 'label' => 'Product'],
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'member', 'label' => 'Customer'],
                ['key' => 'quantity', 'label' => 'Qty', 'align' => 'end'],
                ['key' => 'unit_price', 'label' => 'Unit Price', 'align' => 'end'],
                ['key' => 'line_total', 'label' => 'Total', 'align' => 'end'],
                ['key' => 'profit', 'label' => 'Profit', 'align' => 'end'],
            ],
        ];
    }

    /**
     * @param  array{category?: string|null, status?: string|null}  $filters
     */
    private function stockReport(array $filters, ?int $perPage): array
    {
        $query = Product::query()
            ->with('category')
            ->when(filled($filters['category_id'] ?? null), fn (Builder $query) => $query->where('category_id', $filters['category_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderBy('name');

        $allProducts = (clone $query)->get();

        $rows = $this->paginateOrGet($query, $perPage, fn (Product $product): array => [
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name ?? '—',
            'status' => $product->status->label(),
            'stock' => $product->stock,
            'minimum_stock' => $product->minimum_stock,
            'stock_status' => $product->isOutOfStock() ? 'Out of Stock' : ($product->isLowStock() ? 'Low Stock' : 'In Stock'),
            'purchase_value' => Money::round($product->stock * (float) $product->purchase_price),
            'retail_value' => Money::round($product->stock * (float) $product->selling_price),
        ]);

        return [
            'summary' => [
                'total_products' => $allProducts->count(),
                'active_products' => $allProducts->where('status', ProductStatus::Active)->count(),
                'low_stock_products' => $allProducts->filter(fn (Product $product): bool => $product->isLowStock())->count(),
                'out_of_stock_products' => $allProducts->filter(fn (Product $product): bool => $product->isOutOfStock())->count(),
                'total_retail_value' => Money::round($allProducts->sum(fn (Product $product): float => $product->stock * (float) $product->selling_price)),
            ],
            'rows' => $rows,
            'columns' => [
                ['key' => 'sku', 'label' => 'SKU'],
                ['key' => 'name', 'label' => 'Product'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'stock', 'label' => 'Stock', 'align' => 'end'],
                ['key' => 'minimum_stock', 'label' => 'Min', 'align' => 'end'],
                ['key' => 'stock_status', 'label' => 'Stock Status'],
                ['key' => 'retail_value', 'label' => 'Retail Value', 'align' => 'end'],
            ],
        ];
    }

    /**
     * @param  array{from_date: string, to_date: string}  $filters
     */
    private function paymentQuery(array $filters): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->when(filled($filters['from_date']), fn (Builder $query) => $query->whereDate('paid_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date']), fn (Builder $query) => $query->whereDate('paid_at', '<=', $filters['to_date']));
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array{total: float, transaction_count: int, admission_fee: float, membership_fee: float, pos_sale: float}
     */
    private function paymentSummary(Collection $payments): array
    {
        return [
            'total' => Money::round((float) $payments->sum('amount')),
            'transaction_count' => $payments->count(),
            'admission_fee' => $this->sumByType($payments, PaymentType::AdmissionFee),
            'membership_fee' => $this->sumByType($payments, PaymentType::MembershipFee),
            'pos_sale' => $this->sumByType($payments, PaymentType::PosSale),
        ];
    }

    /**
     * @param  Collection<int, Payment>  $payments
     */
    private function sumByType(Collection $payments, PaymentType $type): float
    {
        return Money::round((float) $payments->where('type', $type)->sum('amount'));
    }

    /**
     * @return array<int, array{key: string, label: string, align?: string}>
     */
    private function collectionColumns(bool $isMonthly = false): array
    {
        return [
            ['key' => $isMonthly ? 'label' : 'label', 'label' => $isMonthly ? 'Month' : 'Date'],
            ['key' => 'admission_fee', 'label' => 'Admission', 'align' => 'end'],
            ['key' => 'membership_fee', 'label' => 'Membership', 'align' => 'end'],
            ['key' => 'pos_sale', 'label' => 'POS', 'align' => 'end'],
            ['key' => 'total', 'label' => 'Total', 'align' => 'end'],
            ['key' => 'count', 'label' => 'Transactions', 'align' => 'end'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMemberRow(Member $member): array
    {
        $daysExpired = $member->membership_expires_at && $member->membership_expires_at->lt(today())
            ? $member->membership_expires_at->diffInDays(today())
            : null;

        return [
            'member_code' => $member->member_code,
            'name' => $member->name,
            'phone' => $member->phone,
            'plan' => $member->membershipPlan?->name ?? '—',
            'status' => $member->status->label(),
            'joined_at' => $member->joined_at?->format('M j, Y') ?? '—',
            'expires_at' => $member->membership_expires_at?->format('M j, Y') ?? '—',
            'days_expired' => $daysExpired,
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): array<string, mixed>  $mapper
     * @return Collection<int, array<string, mixed>>|LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateOrGet(Builder $query, ?int $perPage, callable $mapper): Collection|LengthAwarePaginator
    {
        if ($perPage !== null) {
            return $query
                ->paginate($perPage)
                ->through($mapper)
                ->withQueryString();
        }

        return $query
            ->get()
            ->map($mapper)
            ->values();
    }
}
