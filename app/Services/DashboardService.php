<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array{
     *     total_members: int,
     *     active_members: int,
     *     expired_members: int,
     *     todays_collection: float,
     *     monthly_collection: float,
     *     product_sales: float,
     *     low_stock_products: int,
     *     currency: string
     * }
     */
    public function stats(string $currency = 'USD'): array
    {
        return [
            'total_members' => Member::query()->count(),
            'active_members' => Member::query()->active()->count(),
            'expired_members' => Member::query()->expired()->count(),
            'todays_collection' => (float) Payment::query()->paidToday()->sum('amount'),
            'monthly_collection' => (float) Payment::query()->paidThisMonth()->sum('amount'),
            'product_sales' => (float) Payment::query()
                ->where('type', PaymentType::PosSale)
                ->paidThisMonth()
                ->sum('amount'),
            'low_stock_products' => Product::query()->lowStock()->count(),
            'currency' => $currency,
        ];
    }

    /**
     * @return Collection<int, Member>
     */
    public function recentMembers(int $limit = 5): Collection
    {
        return Member::query()
            ->latest('joined_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function recentPayments(int $limit = 5): Collection
    {
        return Payment::query()
            ->with('member')
            ->latest('paid_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function monthlyRevenue(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $monthExpression = $this->monthExpression('paid_at');

        $totals = Payment::query()
            ->where('paid_at', '>=', $start)
            ->selectRaw("{$monthExpression} as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total): float => (float) $total);

        return $this->buildMonthlySeries($months, $start, $totals, 0.0);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function membershipGrowth(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $monthExpression = $this->monthExpression('joined_at');

        $totals = Member::query()
            ->where('joined_at', '>=', $start->toDateString())
            ->selectRaw("{$monthExpression} as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total): int => (int) $total);

        return $this->buildMonthlySeries($months, $start, $totals, 0);
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * @template TValue of int|float
     *
     * @param  Collection<string, TValue>  $totals
     * @return array{labels: array<int, string>, values: array<int, TValue>}
     */
    private function buildMonthlySeries(int $months, Carbon $start, Collection $totals, int|float $default): array
    {
        $labels = [];
        $values = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonths($i);
            $labels[] = $date->format('M Y');
            $values[] = $totals->get($date->format('Y-m'), $default);
        }

        return compact('labels', 'values');
    }
}
