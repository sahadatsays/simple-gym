<?php

namespace App\Contracts\Repositories;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssetRepositoryInterface extends RepositoryInterface
{
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
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    public function nextAssetCode(): string;

    /**
     * @return Collection<int, string>
     */
    public function distinctLocations(): Collection;
}
