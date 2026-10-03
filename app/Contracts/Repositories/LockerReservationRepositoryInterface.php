<?php

namespace App\Contracts\Repositories;

use App\Models\LockerReservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<LockerReservation>
 */
interface LockerReservationRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, LockerReservation>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
