<?php

namespace App\Contracts\Repositories;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AssetCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, AssetCategory>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginateAssignedAssets(AssetCategory $category, int $perPage): LengthAwarePaginator;

    /**
     * @return Collection<int, Asset>
     */
    public function assignableAssets(AssetCategory $category): Collection;

    public function hasAssets(AssetCategory $category): bool;
}
