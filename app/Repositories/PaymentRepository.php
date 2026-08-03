<?php

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, type?: string|null, payment_method?: string|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<Payment>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['member', 'invoice'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('receipt_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('member', fn ($memberQuery) => $memberQuery->search($search))
                        ->orWhereHas('invoice', function ($invoiceQuery) use ($search): void {
                            $invoiceQuery->where('invoice_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['type'] ?? null), function ($query) use ($filters): void {
                $query->where('type', $filters['type']);
            })
            ->when(filled($filters['payment_method'] ?? null), function ($query) use ($filters): void {
                $query->where('payment_method', $filters['payment_method']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('paid_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('paid_at', '<=', $filters['to_date']);
            })
            ->latest('paid_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function nextReceiptNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = "RCP-{$today}-";

        $latest = $this->newQuery()
            ->where('receipt_number', 'like', "{$prefix}%")
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
