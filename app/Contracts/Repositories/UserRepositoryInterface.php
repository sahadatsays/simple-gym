<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginateWithRoles(int $perPage): LengthAwarePaginator;
}
