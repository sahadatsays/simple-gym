<?php

namespace App\Repositories;

use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseCategoryRepository extends BaseRepository implements ExpenseCategoryRepositoryInterface
{
    public function __construct(ExpenseCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<ExpenseCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->withCount('expenses')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function hasExpenses(ExpenseCategory $category): bool
    {
        return $category->expenses()->withTrashed()->exists();
    }
}
