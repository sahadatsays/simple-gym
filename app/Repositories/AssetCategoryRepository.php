<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetCategoryRepositoryInterface;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssetCategoryRepository extends BaseRepository implements AssetCategoryRepositoryInterface
{
    public function __construct(AssetCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, AssetCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->withCount([
                'assets',
                'assets as assigned_assets_count' => fn ($query) => $query->withTrashed(),
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), function ($query) use ($filters): void {
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginateAssignedAssets(AssetCategory $category, int $perPage): LengthAwarePaginator
    {
        return $category->assets()
            ->latest('purchased_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'assets_page')
            ->withQueryString();
    }

    /**
     * @return Collection<int, Asset>
     */
    public function assignableAssets(AssetCategory $category): Collection
    {
        return Asset::query()
            ->where('asset_category_id', '!=', $category->id)
            ->orderBy('name')
            ->orderBy('asset_code')
            ->get(['id', 'name', 'asset_code']);
    }

    public function hasAssets(AssetCategory $category): bool
    {
        return $category->assets()->withTrashed()->exists();
    }
}
