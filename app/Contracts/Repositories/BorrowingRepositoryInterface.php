<?php

namespace App\Contracts\Repositories;

use App\Models\Borrowing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BorrowingRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Borrowing>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function nextBorrowingNumber(): string;
}
