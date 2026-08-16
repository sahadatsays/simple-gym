<?php

namespace App\Repositories;

use App\Contracts\Repositories\InvestmentRepositoryInterface;
use App\Models\Investment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvestmentRepository extends BaseRepository implements InvestmentRepositoryInterface
{
    public function __construct(Investment $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     investment_category_id?: int|string|null,
     *     payment_method?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Investment>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('investment_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['investment_category_id'] ?? null), function ($query) use ($filters): void {
                $query->where('investment_category_id', $filters['investment_category_id']);
            })
            ->when(filled($filters['payment_method'] ?? null), function ($query) use ($filters): void {
                $query->where('payment_method', $filters['payment_method']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('invested_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('invested_at', '<=', $filters['to_date']);
            })
            ->latest('invested_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function nextInvestmentNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "INV-{$today}-";

        $latest = Investment::query()
            ->withTrashed()
            ->where('investment_number', 'like', "{$prefix}%")
            ->orderByDesc('investment_number')
            ->value('investment_number');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
