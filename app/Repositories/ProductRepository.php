<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

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
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['barcode'] ?? null), function ($query) use ($filters): void {
                $query->where('barcode', $filters['barcode']);
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['category'] ?? null), function ($query) use ($filters): void {
                $query->where('category', $filters['category']);
            })
            ->when(filled($filters['stock'] ?? null), function ($query) use ($filters): void {
                match ($filters['stock']) {
                    'low' => $query->lowStock(),
                    'out' => $query->where('stock', '<=', 0),
                    'available' => $query->where('stock', '>', 0),
                    default => null,
                };
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByBarcode(string $barcode): ?Product
    {
        return $this->newQuery()
            ->where('barcode', $barcode)
            ->first();
    }

    public function findActiveForPosByBarcode(string $barcode): ?Product
    {
        return $this->newQuery()
            ->active()
            ->where('barcode', $barcode)
            ->where('stock', '>', 0)
            ->first();
    }

    public function findActiveForPosBySku(string $sku): ?Product
    {
        return $this->newQuery()
            ->active()
            ->where('sku', $sku)
            ->where('stock', '>', 0)
            ->first();
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->newQuery()
            ->where('sku', $sku)
            ->first();
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchForPos(?string $search, ?string $category, int $limit = 24): Collection
    {
        return $this->newQuery()
            ->active()
            ->where('stock', '>', 0)
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when(filled($category), fn ($query) => $query->where('category', $category))
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->newQuery()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /**
     * @return Collection<int, object{category: string, products_count: int, active_count: int}>
     */
    public function categorySummaries(): Collection
    {
        return $this->newQuery()
            ->selectRaw('category, COUNT(*) as products_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count', [ProductStatus::Active->value])
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
    }
}
