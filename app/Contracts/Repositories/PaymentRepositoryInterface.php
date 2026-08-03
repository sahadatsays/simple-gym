<?php

namespace App\Contracts\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, type?: string|null, payment_method?: string|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<Payment>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function nextReceiptNumber(): string;
}
