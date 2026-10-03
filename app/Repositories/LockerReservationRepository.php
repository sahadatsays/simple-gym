<?php

namespace App\Repositories;

use App\Contracts\Repositories\LockerReservationRepositoryInterface;
use App\Models\LockerReservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LockerReservationRepository extends BaseRepository implements LockerReservationRepositoryInterface
{
    public function __construct(LockerReservation $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, LockerReservation>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['locker', 'member', 'invoice', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->whereHas('locker', function ($locker) use ($search): void {
                        $locker->where('locker_number', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    })->orWhereHas('member', function ($member) use ($search): void {
                        $member->where('name', 'like', "%{$search}%")
                            ->orWhere('member_code', 'like', "%{$search}%");
                    });
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->latest('start_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
