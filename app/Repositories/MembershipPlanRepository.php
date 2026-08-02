<?php

namespace App\Repositories;

use App\Contracts\Repositories\MembershipPlanRepositoryInterface;
use App\Models\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MembershipPlanRepository extends BaseRepository implements MembershipPlanRepositoryInterface
{
    public function __construct(MembershipPlan $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<MembershipPlan>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->withCount('members')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function hasMembers(MembershipPlan $plan): bool
    {
        return $plan->members()->exists();
    }
}
