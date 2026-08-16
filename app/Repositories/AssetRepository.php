<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetRepositoryInterface;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface
{
    public function __construct(Asset $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     asset_category_id?: int|string|null,
     *     status?: string|null,
     *     condition?: string|null,
     *     location?: string|null
     * }  $filters
     * @return LengthAwarePaginator<Asset>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('asset_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['asset_category_id'] ?? null), function ($query) use ($filters): void {
                $query->where('asset_category_id', $filters['asset_category_id']);
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when(filled($filters['condition'] ?? null), function ($query) use ($filters): void {
                $query->where('condition', $filters['condition']);
            })
            ->when(filled($filters['location'] ?? null), function ($query) use ($filters): void {
                $query->where('location', $filters['location']);
            })
            ->latest('purchased_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function nextAssetCode(): string
    {
        $today = now()->format('Ymd');
        $prefix = "AST-{$today}-";

        $latest = Asset::query()
            ->withTrashed()
            ->where('asset_code', 'like', "{$prefix}%")
            ->orderByDesc('asset_code')
            ->value('asset_code');

        $nextSequence = $latest
            ? ((int) substr($latest, -5)) + 1
            : 1;

        return $prefix.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, string>
     */
    public function distinctLocations(): Collection
    {
        return $this->newQuery()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');
    }
}
