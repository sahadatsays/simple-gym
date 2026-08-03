<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductReportService
{
    /**
     * @param  array{from_date?: string|null, to_date?: string|null}  $filters
     * @return array{
     *     total_units_sold: int,
     *     total_revenue: float,
     *     total_cost: float,
     *     gross_profit: float,
     *     average_order_value: float,
     *     sales_count: int,
     *     units_sold_this_month: int,
     *     revenue_this_month: float,
     *     last_sale_at: ?Carbon
     * }
     */
    public function summary(Product $product, array $filters = []): array
    {
        $query = $this->salesQuery($product, $filters);

        $aggregate = (clone $query)
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_units_sold')
            ->selectRaw('COALESCE(SUM(line_total), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as sales_count')
            ->first();

        $totalRevenue = (float) ($aggregate->total_revenue ?? 0);
        $totalCost = (float) ($aggregate->total_cost ?? 0);
        $salesCount = (int) ($aggregate->sales_count ?? 0);

        $monthAggregate = (clone $this->salesQuery($product, [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
            ->selectRaw('COALESCE(SUM(quantity), 0) as units_sold_this_month')
            ->selectRaw('COALESCE(SUM(line_total), 0) as revenue_this_month')
            ->first();

        $lastSaleAt = (clone $query)->max('sold_at');

        return [
            'total_units_sold' => (int) ($aggregate->total_units_sold ?? 0),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $totalRevenue - $totalCost,
            'average_order_value' => $salesCount > 0 ? round($totalRevenue / $salesCount, 2) : 0.0,
            'sales_count' => $salesCount,
            'units_sold_this_month' => (int) ($monthAggregate->units_sold_this_month ?? 0),
            'revenue_this_month' => (float) ($monthAggregate->revenue_this_month ?? 0),
            'last_sale_at' => $lastSaleAt ? Carbon::parse($lastSaleAt) : null,
        ];
    }

    /**
     * @param  array{from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<ProductSale>
     */
    public function paginateSales(Product $product, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->salesQuery($product, $filters)
            ->with(['payment', 'member', 'invoice'])
            ->latest('sold_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, array{label: string, month: string, units_sold: int, revenue: float, gross_profit: float}>
     */
    public function monthlyBreakdown(Product $product, int $months = 6): Collection
    {
        $sales = ProductSale::query()
            ->where('product_id', $product->id)
            ->where('sold_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->get();

        return collect(range($months - 1, 0))
            ->map(function (int $offset) use ($sales): array {
                $month = now()->subMonths($offset)->startOfMonth();

                $monthSales = $sales->filter(
                    fn (ProductSale $sale): bool => $sale->sold_at->isSameMonth($month)
                );

                $revenue = round($monthSales->sum(fn (ProductSale $sale): float => (float) $sale->line_total), 2);
                $grossProfit = round($monthSales->sum(fn (ProductSale $sale): float => $sale->grossProfit()), 2);

                return [
                    'label' => $month->format('M Y'),
                    'month' => $month->format('Y-m'),
                    'units_sold' => (int) $monthSales->sum('quantity'),
                    'revenue' => $revenue,
                    'gross_profit' => $grossProfit,
                ];
            })
            ->reverse()
            ->values();
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function stockMovements(Product $product, int $limit = 10): Collection
    {
        return ActivityLog::query()
            ->with('user')
            ->where('subject_type', $product->getMorphClass())
            ->where('subject_id', $product->id)
            ->whereIn('action', ['product.stock_adjusted', 'product.created', 'product.updated'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array{from_date?: string|null, to_date?: string|null}  $filters
     */
    private function salesQuery(Product $product, array $filters = [])
    {
        return ProductSale::query()
            ->where('product_id', $product->id)
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('sold_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('sold_at', '<=', $filters['to_date']);
            });
    }
}
