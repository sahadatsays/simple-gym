<?php

namespace App\Contracts\Repositories;

use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RfidCardRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<RfidCard>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function findByCardNumber(string $cardNumber): ?RfidCard;

    /**
     * @return Collection<int, RfidCard>
     */
    public function activeCardsForMember(Member $member): Collection;

    public function disableActiveCardsForMember(Member $member): void;
}
