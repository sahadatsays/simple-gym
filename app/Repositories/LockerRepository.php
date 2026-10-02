<?php

namespace App\Repositories;

use App\Contracts\Repositories\LockerRepositoryInterface;
use App\Models\Locker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LockerRepository extends BaseRepository implements LockerRepositoryInterface
{
    public function __construct(Locker $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, Locker>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('creator')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('locker_number', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->orderBy('locker_number')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
