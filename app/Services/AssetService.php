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
            $payload = $this->sanitizeUpdatePayload($asset, $data);

            $updatedAsset = $this->assets->update($asset, $payload);

            $this->activityLogger->log('asset.updated', $updatedAsset, 'Asset updated', [
                'asset_code' => $updatedAsset->asset_code,
            ]);

            return $updatedAsset;
        });
    }

    public function delete(Asset $asset): void
    {
        if (! $asset->isDeletable()) {
            throw new \InvalidArgumentException('Disposed assets cannot be deleted.');
        }

        $this->transaction(function () use ($asset): void {
            $this->activityLogger->log('asset.deleted', $asset, 'Asset deleted', [
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
            ]);

            $this->assets->delete($asset);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeUpdatePayload(Asset $asset, array $data): array
    {
        $payload = $data;

        if (! $asset->status?->isOperational() || $asset->disposal()->exists()) {
            unset($payload['status']);

            return $payload;
        }

        if (isset($payload['status'])) {
            $status = AssetStatus::tryFrom((string) $payload['status']);

            if ($status === null || ! $status->isOperational()) {
                unset($payload['status']);
            }
        }

        return $payload;
    }
}
