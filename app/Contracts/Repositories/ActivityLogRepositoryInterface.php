<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface extends RepositoryInterface
{
    public function paginateLatest(int $perPage): LengthAwarePaginator;
}
