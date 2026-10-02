<?php

namespace App\Contracts\Repositories;

use App\Models\Locker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<Locker>
 */
interface LockerRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, Locker>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
