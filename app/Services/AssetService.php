<?php

namespace App\Services;

use App\Contracts\Repositories\AssetRepositoryInterface;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Support\ActivityLogger;

class AssetService extends BaseService
{
    public function __construct(
        private AssetRepositoryInterface $assets,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): Asset
    {
        return $this->transaction(function () use ($data, $createdBy): Asset {
            $payload = $data;
            $payload['asset_code'] = $this->assets->nextAssetCode();
            $payload['created_by'] = $createdBy;
            $payload['status'] = $payload['status'] ?? AssetStatus::Active->value;
            $payload['condition'] = $payload['condition'] ?? AssetCondition::New->value;
            $payload['current_value'] = $payload['current_value'] ?? $payload['purchase_price'];

            $asset = $this->assets->create($payload);

            $this->activityLogger->log('asset.created', $asset, 'Asset created', [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
            ]);

            return $asset;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        return $this->transaction(function () use ($asset, $data): Asset {
            $updatedAsset = $this->assets->update($asset, $data);

            $this->activityLogger->log('asset.updated', $updatedAsset, 'Asset updated', [
                'asset_code' => $updatedAsset->asset_code,
            ]);

            return $updatedAsset;
        });
    }

    public function delete(Asset $asset): void
    {
        $this->transaction(function () use ($asset): void {
            $this->activityLogger->log('asset.deleted', $asset, 'Asset deleted', [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
            ]);

            $this->assets->delete($asset);
        });
    }
}
