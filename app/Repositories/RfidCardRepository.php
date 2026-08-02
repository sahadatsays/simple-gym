<?php

namespace App\Repositories;

use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Enums\RfidCardStatus;
use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RfidCardRepository extends BaseRepository implements RfidCardRepositoryInterface
{
    public function __construct(RfidCard $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<RfidCard>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('member')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('card_number', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search): void {
                            $memberQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('member_code', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByCardNumber(string $cardNumber): ?RfidCard
    {
        return $this->newQuery()->where('card_number', $cardNumber)->first();
    }

    /**
     * @return Collection<int, RfidCard>
     */
    public function activeCardsForMember(Member $member): Collection
    {
        return $this->newQuery()
            ->where('member_id', $member->id)
            ->where('status', RfidCardStatus::Active)
            ->get();
    }

    public function disableActiveCardsForMember(Member $member): void
    {
        $this->newQuery()
            ->where('member_id', $member->id)
            ->where('status', RfidCardStatus::Active)
            ->update([
                'status' => RfidCardStatus::Disabled,
            ]);
    }
}
