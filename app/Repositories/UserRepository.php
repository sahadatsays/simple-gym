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

    public function findByUsername(string $username): ?User
    {
        return $this->newQuery()->where('username', $username)->first();
    }

    /**
     * @param  array{search?: string|null, role?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<User>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('roles')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['role'] ?? null), function ($query) use ($filters): void {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $filters['role']));
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
