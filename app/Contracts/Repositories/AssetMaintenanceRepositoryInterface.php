<?php

namespace App\Contracts\Repositories;

use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetMaintenanceRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{
     *     search?: string|null,
     *     asset_id?: int|string|null,
     *     type?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<AssetMaintenance>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
