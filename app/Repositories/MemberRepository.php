<?php

namespace App\Repositories;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberRepository extends BaseRepository implements MemberRepositoryInterface
{
    public function __construct(Member $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null, membership_plan_id?: int|string|null, gender?: string|null}  $filters
     * @return LengthAwarePaginator<Member>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('membershipPlan')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('member_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rfid_card', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['membership_plan_id'] ?? null), function ($query) use ($filters): void {
                $query->where('membership_plan_id', $filters['membership_plan_id']);
            })
            ->when(filled($filters['gender'] ?? null), function ($query) use ($filters): void {
                $query->where('gender', $filters['gender']);
            })
            ->latest('joined_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByMemberCode(string $memberCode): ?Member
    {
        return $this->newQuery()->where('member_code', $memberCode)->first();
    }

    public function nextMemberCode(): string
    {
        $nextNumber = (int) $this->newQuery()->withTrashed()->max('id') + 1;

        return 'M'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
