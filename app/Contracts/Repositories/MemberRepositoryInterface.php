<?php

namespace App\Contracts\Repositories;

use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null, membership_plan_id?: int|string|null, gender?: string|null}  $filters
     * @return LengthAwarePaginator<Member>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function findByMemberCode(string $memberCode): ?Member;

    public function findByPhone(string $phone): ?Member;

    public function nextMemberCode(): string;
}
