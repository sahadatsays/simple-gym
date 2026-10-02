<?php

namespace App\Repositories;

use App\Contracts\Repositories\BorrowingRepositoryInterface;
use App\Models\Borrowing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
