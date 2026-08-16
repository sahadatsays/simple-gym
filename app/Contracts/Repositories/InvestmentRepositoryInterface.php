<?php

namespace App\Contracts\Repositories;

use App\Models\Investment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvestmentRepositoryInterface extends RepositoryInterface
{
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
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function nextInvestmentNumber(): string;
}
