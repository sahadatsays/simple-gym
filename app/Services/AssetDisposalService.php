<?php

namespace App\Services;

use App\Contracts\Repositories\AssetDisposalRepositoryInterface;
use App\Enums\AssetDisposalType;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Support\ActivityLogger;

class AssetDisposalService extends BaseService
{
    public function __construct(
        private AssetDisposalRepositoryInterface $disposals,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): AssetDisposal
    {
        return $this->transaction(function () use ($data, $createdBy): AssetDisposal {
            $disposalType = AssetDisposalType::from($data['disposal_type']);

            $payload = $data;
            $payload['created_by'] = $createdBy;

            if (! $disposalType->requiresSaleAmount()) {
                $payload['sale_amount'] = null;
            }

            $disposal = $this->disposals->create($payload);

            Asset::query()
                ->whereKey($disposal->asset_id)
                ->update(['status' => $disposalType->toAssetStatus()]);

            $this->activityLogger->log('asset_disposal.created', $disposal, 'Asset disposed', [
                'asset_id' => $disposal->asset_id,
                'disposal_type' => $disposal->disposal_type->value,
            ]);

            return $disposal->load(['asset', 'creator']);
        });
    }
}
