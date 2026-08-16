<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetDisposalRepositoryInterface;
use App\Models\AssetDisposal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssetDisposalRepository extends BaseRepository implements AssetDisposalRepositoryInterface
{
    public function __construct(AssetDisposal $model)
    {
        parent::__construct($model);
    }

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
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['asset.category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('buyer', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($assetQuery) use ($search): void {
                            $assetQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['asset_id'] ?? null), function ($query) use ($filters): void {
                $query->where('asset_id', $filters['asset_id']);
            })
            ->when(filled($filters['disposal_type'] ?? null), function ($query) use ($filters): void {
                $query->where('disposal_type', $filters['disposal_type']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('disposed_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('disposed_at', '<=', $filters['to_date']);
            })
            ->latest('disposed_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
