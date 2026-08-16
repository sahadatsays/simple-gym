<?php

namespace App\Repositories;

use App\Contracts\Repositories\InvestmentCategoryRepositoryInterface;
use App\Models\InvestmentCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvestmentCategoryRepository extends BaseRepository implements InvestmentCategoryRepositoryInterface
{
    public function __construct(InvestmentCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<InvestmentCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->withCount('investments')
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

    public function hasInvestments(InvestmentCategory $category): bool
    {
        return $category->investments()->withTrashed()->exists();
    }
}
