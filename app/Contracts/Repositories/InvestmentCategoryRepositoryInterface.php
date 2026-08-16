<?php

namespace App\Contracts\Repositories;

use App\Models\InvestmentCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvestmentCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<InvestmentCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function hasInvestments(InvestmentCategory $category): bool;
}
