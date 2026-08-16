<?php

namespace App\Services;

use App\Contracts\Repositories\AssetMaintenanceRepositoryInterface;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Support\ActivityLogger;
use App\Support\AssetMaintenanceAttachmentStorage;
use Illuminate\Http\UploadedFile;

class AssetMaintenanceService extends BaseService
{
    public function __construct(
        private AssetMaintenanceRepositoryInterface $maintenances,
        private AssetMaintenanceAttachmentStorage $attachmentStorage,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        array $data,
        ?UploadedFile $attachment = null,
        ?int $createdBy = null,
        ?AssetStatus $assetStatus = null,
    ): AssetMaintenance {
        return $this->transaction(function () use ($data, $attachment, $createdBy, $assetStatus): AssetMaintenance {
            $payload = $data;
            $payload['created_by'] = $createdBy;

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $maintenance = $this->maintenances->create($payload);

            if ($assetStatus !== null) {
                Asset::query()
                    ->whereKey($maintenance->asset_id)
                    ->update(['status' => $assetStatus]);
            }

            $this->activityLogger->log('asset_maintenance.created', $maintenance, 'Asset maintenance recorded', [
                'asset_id' => $maintenance->asset_id,
                'type' => $maintenance->type->value,
            ]);

            return $maintenance->load(['asset', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        AssetMaintenance $maintenance,
        array $data,
        ?UploadedFile $attachment = null,
        bool $removeAttachment = false,
        ?AssetStatus $assetStatus = null,
    ): AssetMaintenance {
        return $this->transaction(function () use ($maintenance, $data, $attachment, $removeAttachment, $assetStatus): AssetMaintenance {
            $payload = $data;
            $previousAttachment = $maintenance->attachment_path;

            if ($removeAttachment) {
                $payload['attachment_path'] = null;
            }

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $updatedMaintenance = $this->maintenances->update($maintenance, $payload);

            if ($assetStatus !== null) {
                Asset::query()
                    ->whereKey($updatedMaintenance->asset_id)
                    ->update(['status' => $assetStatus]);
            }

            if ($previousAttachment !== null && ($removeAttachment || $attachment !== null)) {
                $this->attachmentStorage->delete($previousAttachment);
            }

            $this->activityLogger->log('asset_maintenance.updated', $updatedMaintenance, 'Asset maintenance updated', [
                'asset_id' => $updatedMaintenance->asset_id,
            ]);

            return $updatedMaintenance->load(['asset', 'creator']);
        });
    }

    public function delete(AssetMaintenance $maintenance): void
    {
        $this->transaction(function () use ($maintenance): void {
            $this->activityLogger->log('asset_maintenance.deleted', $maintenance, 'Asset maintenance deleted', [
                'asset_id' => $maintenance->asset_id,
            ]);

            $attachmentPath = $maintenance->attachment_path;

            $this->maintenances->delete($maintenance);

            $this->attachmentStorage->delete($attachmentPath);
        });
    }
}
