<?php

namespace App\Contracts\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     category?: string|null,
     *     stock?: string|null,
     *     barcode?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Product>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function findByBarcode(string $barcode): ?Product;

    public function findBySku(string $sku): ?Product;

    /**
     * @return list<string>
     */
    public function categories(): array;

    /**
     * @return Collection<int, Product>
     */
    public function searchForPos(?string $search, ?string $category, int $limit = 24): Collection;
}
