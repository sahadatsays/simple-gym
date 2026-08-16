<?php

namespace App\Contracts\Repositories;

use App\Models\AssetDisposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetDisposalRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array{
     *     search?: string|null,
     *     asset_id?: int|string|null,
     *     disposal_type?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<AssetDisposal>
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
}
