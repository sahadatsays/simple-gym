<?php

namespace App\Repositories;

use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function __construct(Expense $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     expense_category_id?: int|string|null,
     *     payment_method?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Expense>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('paid_to', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['expense_category_id'] ?? null), function ($query) use ($filters): void {
                $query->where('expense_category_id', $filters['expense_category_id']);
            })
            ->when(filled($filters['payment_method'] ?? null), function ($query) use ($filters): void {
                $query->where('payment_method', $filters['payment_method']);
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('expensed_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('expensed_at', '<=', $filters['to_date']);
            })
            ->latest('expensed_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function nextExpenseNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "EXP-{$today}-";

        $latest = Expense::query()
            ->withTrashed()
            ->where('expense_number', 'like', "{$prefix}%")
            ->orderByDesc('expense_number')
            ->value('expense_number');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
