<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetMaintenanceRepositoryInterface;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssetMaintenanceRepository extends BaseRepository implements AssetMaintenanceRepositoryInterface
{
    public function __construct(AssetMaintenance $model)
    {
        parent::__construct($model);
    }

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
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->newQuery()
            ->with(['asset.category', 'creator'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($nested) use ($search): void {
                    $nested->where('description', 'like', "%{$search}%")
                        ->orWhere('service_provider', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($assetQuery) use ($search): void {
                            $assetQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['asset_id'] ?? null), function ($query) use ($filters): void {
                $query->where('asset_id', $filters['asset_id']);
            })
            ->when(filled($filters['type'] ?? null), function ($query) use ($filters): void {
                $query->where('type', $filters['type']);
            })
            ->when(filled($filters['from_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('maintained_at', '>=', $filters['from_date']);
            })
            ->when(filled($filters['to_date'] ?? null), function ($query) use ($filters): void {
                $query->whereDate('maintained_at', '<=', $filters['to_date']);
            })
            ->latest('maintained_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
