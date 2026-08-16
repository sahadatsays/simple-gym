<?php

namespace App\Contracts\Repositories;

use App\Models\ExpenseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ExpenseCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<ExpenseCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function hasExpenses(ExpenseCategory $category): bool;
}
