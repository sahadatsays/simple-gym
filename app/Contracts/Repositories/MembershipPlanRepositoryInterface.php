<?php

namespace App\Contracts\Repositories;

use App\Models\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MembershipPlanRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<MembershipPlan>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function hasMembers(MembershipPlan $plan): bool;
}
