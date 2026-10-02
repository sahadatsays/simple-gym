<?php

namespace App\Repositories;

use App\Contracts\Repositories\BorrowingRepositoryInterface;
use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BorrowingRepository extends BaseRepository implements BorrowingRepositoryInterface
{
    public function __construct(Borrowing $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Borrowing>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with('creator')
            ->withSum('repayments', 'amount')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('borrowing_no', 'like', "%{$search}%")
                        ->orWhere('lender_name', 'like', "%{$search}%")
                        ->orWhere('lender_phone', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('borrowing_date', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('borrowing_date', '<=', $filters['to_date']);
            })
            ->latest('borrowing_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function nextBorrowingNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "BOR-{$today}-";

        $latest = Borrowing::query()
            ->where('borrowing_no', 'like', "{$prefix}%")
            ->orderByDesc('borrowing_no')
            ->value('borrowing_no');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    public function nextRepaymentNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "RPY-{$today}-";

        $latest = BorrowingRepayment::query()
            ->where('repayment_no', 'like', "{$prefix}%")
            ->orderByDesc('repayment_no')
            ->value('repayment_no');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, Borrowing>
     */
    public function repayable(): Collection
    {
        return $this->newQuery()
            ->withSum('repayments', 'amount')
            ->whereIn('status', [
                BorrowingStatus::Active->value,
                BorrowingStatus::PartiallyRepaid->value,
            ])
            ->orderByDesc('borrowing_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Borrowing $borrowing): bool => $borrowing->acceptsRepayment())
            ->values();
    }
}
