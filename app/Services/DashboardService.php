<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Support\DashboardDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array{
     *     new_registrations: int,
     *     active_members: int,
     *     expired_members: int,
     *     period_revenue: float,
     *     product_sales: float,
     *     low_stock_products: int,
     *     currency: string,
     *     range_label: string
     * }
     */
    public function stats(DashboardDateRange $range, string $currency = 'USD'): array
    {
        $from = $range->from->toDateString();
        $to = $range->to->toDateString();

        return [
            'new_registrations' => Member::query()
                ->whereDate('joined_at', '>=', $from)
                ->whereDate('joined_at', '<=', $to)
                ->count(),
            'active_members' => Member::query()->active()->count(),
            'expired_members' => Member::query()
                ->expired()
                ->whereDate('membership_expires_at', '>=', $from)
                ->whereDate('membership_expires_at', '<=', $to)
                ->count(),
            'period_revenue' => (float) $this->paymentsInRange($range)->sum('amount'),
            'product_sales' => (float) $this->paymentsInRange($range)
                ->where('type', PaymentType::PosSale)
                ->sum('amount'),
            'low_stock_products' => Product::query()->lowStock()->count(),
            'currency' => $currency,
            'range_label' => $range->label(),
        ];
    }

    /**
     * @return Collection<int, Member>
     */
    public function recentRegistrations(DashboardDateRange $range, int $limit = 8): Collection
    {
        return Member::query()
            ->whereDate('joined_at', '>=', $range->from->toDateString())
            ->whereDate('joined_at', '<=', $range->to->toDateString())
            ->latest('joined_at')
            ->limit($limit)
            ->get(['id', 'name', 'member_code', 'joined_at', 'status']);
    }

    /**
     * @return Collection<int, Payment>
     */
    public function recentPayments(DashboardDateRange $range, int $limit = 8): Collection
    {
        return $this->paymentsInRange($range)
            ->with('member:id,name,member_code')
            ->latest('paid_at')
            ->limit($limit)
            ->get(['id', 'member_id', 'type', 'amount', 'paid_at', 'receipt_number', 'reference']);
    }

    /**
     * @return Collection<int, Product>
     */
    public function lowStockProducts(int $limit = 8): Collection
    {
        return Product::query()
            ->lowStock()
            ->orderBy('stock')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'stock', 'minimum_stock', 'selling_price']);
    }

    /**
     * Open POS orders with a due date that is overdue or due within the lookahead window.
     *
     * @return Collection<int, Invoice>
     */
    public function upcomingDueOrders(?int $limit = null): Collection
    {
        $limit ??= (int) config('gym.dashboard.due_orders_limit', 8);
        $lookaheadDays = (int) config('gym.dashboard.due_orders_lookahead_days', 30);

        return Invoice::query()
            ->with(['member:id,name,member_code', 'payments'])
            ->where('type', InvoiceType::PosSale)
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays($lookaheadDays)->endOfDay())
            ->orderBy('due_at')
            ->limit($limit)
            ->get([
                'id',
                'member_id',
                'invoice_number',
                'status',
                'total',
                'due_at',
                'issued_at',
            ]);
    }

    /**
     * @return array{
     *     total_owner_investment: float,
     *     total_asset_purchase_value: float,
     *     current_asset_value: float,
     *     total_maintenance_cost: float,
     *     active_assets: int,
     *     assets_under_maintenance: int,
     *     assets_requiring_maintenance: int
     * }
     */
    public function assetInvestmentStats(DashboardDateRange $range): array
    {
        $from = $range->from->toDateString();
        $to = $range->to->toDateString();

        return [
            'total_owner_investment' => (float) Investment::query()
                ->whereDate('invested_at', '>=', $from)
                ->whereDate('invested_at', '<=', $to)
                ->sum('amount'),
            'total_asset_purchase_value' => (float) Asset::query()
                ->whereDate('purchased_at', '>=', $from)
                ->whereDate('purchased_at', '<=', $to)
                ->sum('purchase_price'),
            'current_asset_value' => (float) Asset::query()
                ->where('status', AssetStatus::Active)
                ->sum(DB::raw('COALESCE(current_value, 0)')),
            'total_maintenance_cost' => (float) AssetMaintenance::query()
                ->whereDate('maintained_at', '>=', $from)
                ->whereDate('maintained_at', '<=', $to)
                ->sum(DB::raw('COALESCE(cost, 0)')),
            'active_assets' => Asset::query()->where('status', AssetStatus::Active)->count(),
            'assets_under_maintenance' => Asset::query()
                ->where('status', AssetStatus::UnderMaintenance)
                ->count(),
            'assets_requiring_maintenance' => $this->assetsRequiringMaintenanceCount(),
        ];
    }

    /**
     * @return Collection<int, Investment>
     */
    public function recentInvestments(DashboardDateRange $range, int $limit = 8): Collection
    {
        return Investment::query()
            ->with('category:id,name')
            ->whereDate('invested_at', '>=', $range->from->toDateString())
            ->whereDate('invested_at', '<=', $range->to->toDateString())
            ->latest('invested_at')
            ->latest('id')
            ->limit($limit)
            ->get([
                'id',
                'investment_number',
                'invested_at',
                'investment_category_id',
                'amount',
                'payment_method',
            ]);
    }

    /**
     * @return Collection<int, Asset>
     */
    public function recentAssetPurchases(DashboardDateRange $range, int $limit = 8): Collection
    {
        return Asset::query()
            ->with('category:id,name')
            ->whereDate('purchased_at', '>=', $range->from->toDateString())
            ->whereDate('purchased_at', '<=', $range->to->toDateString())
            ->latest('purchased_at')
            ->latest('id')
            ->limit($limit)
            ->get([
                'id',
                'asset_code',
                'name',
                'asset_category_id',
                'purchased_at',
                'purchase_price',
                'status',
            ]);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function revenueSeries(DashboardDateRange $range): array
    {
        $expression = $this->dateBucketExpression('paid_at', $range);

        $totals = $this->paymentsInRange($range)
            ->selectRaw("{$expression} as bucket, SUM(amount) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total): float => (float) $total);

        return $this->buildBucketSeries($range, $totals, 0.0);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function registrationSeries(DashboardDateRange $range): array
    {
        $expression = $this->dateBucketExpression('joined_at', $range);

        $totals = Member::query()
            ->whereDate('joined_at', '>=', $range->from->toDateString())
            ->whereDate('joined_at', '<=', $range->to->toDateString())
            ->selectRaw("{$expression} as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total): int => (int) $total);

        return $this->buildBucketSeries($range, $totals, 0);
    }

    private function assetsRequiringMaintenanceCount(): int
    {
        $latestMaintenances = AssetMaintenance::query()
            ->selectRaw('asset_id, MAX(id) as id')
            ->groupBy('asset_id');

        return Asset::query()
            ->maintainable()
            ->joinSub($latestMaintenances, 'latest_maintenances', function ($join): void {
                $join->on('assets.id', '=', 'latest_maintenances.asset_id');
            })
            ->join('asset_maintenances', 'asset_maintenances.id', '=', 'latest_maintenances.id')
            ->whereNotNull('asset_maintenances.next_maintenance_at')
            ->whereDate('asset_maintenances.next_maintenance_at', '<=', today())
            ->count();
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    private function paymentsInRange(DashboardDateRange $range)
    {
        return Payment::query()
            ->where('paid_at', '>=', $range->from)
            ->where('paid_at', '<=', $range->to);
    }

    private function dateBucketExpression(string $column, DashboardDateRange $range): string
    {
        $driver = DB::connection()->getDriverName();
        $useMonthly = $range->dayCount() > 62;

        if ($useMonthly) {
            return match ($driver) {
                'sqlite' => "strftime('%Y-%m', {$column})",
                'pgsql' => "to_char({$column}, 'YYYY-MM')",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            };
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM-DD')",
            default => "DATE_FORMAT({$column}, '%Y-%m-%d')",
        };
    }

    /**
     * @template TValue of int|float
     *
     * @param  Collection<string, TValue>  $totals
     * @return array{labels: array<int, string>, values: array<int, TValue>}
     */
    private function buildBucketSeries(
        DashboardDateRange $range,
        Collection $totals,
        int|float $default,
    ): array {
        $labels = [];
        $values = [];
        $useMonthly = $range->dayCount() > 62;

        if ($useMonthly) {
            $cursor = $range->from->copy()->startOfMonth();
            $end = $range->to->copy()->startOfMonth();

            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $values[] = $totals->get($key, $default);
                $cursor->addMonth();
            }

            return compact('labels', 'values');
        }

        $cursor = $range->from->copy()->startOfDay();
        $end = $range->to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M j');
            $values[] = $totals->get($key, $default);
            $cursor->addDay();
        }

        return compact('labels', 'values');
    }
}
