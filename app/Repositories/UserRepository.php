<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginateWithRoles(int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('roles')
            ->latest()
            ->paginate($perPage);
    }
}
