<?php

namespace App\Services;

use App\Contracts\Repositories\AssetCategoryRepositoryInterface;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class AssetCategoryService extends BaseService
{
    public function __construct(
        private AssetCategoryRepositoryInterface $categories,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): AssetCategory
    {
        return $this->transaction(function () use ($data, $createdBy): AssetCategory {
            $payload = $data;
            $payload['created_by'] = $createdBy;

            $category = $this->categories->create($payload);

            $this->activityLogger->log('asset_category.created', $category, 'Asset category created', [
                'name' => $category->name,
            ]);

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AssetCategory $category, array $data): AssetCategory
    {
        return $this->transaction(function () use ($category, $data): AssetCategory {
            $updatedCategory = $this->categories->update($category, $data);

            $this->activityLogger->log('asset_category.updated', $updatedCategory, 'Asset category updated', [
                'name' => $updatedCategory->name,
            ]);

            return $updatedCategory;
        });
    }

    public function delete(AssetCategory $category): void
    {
        if ($this->categories->hasAssets($category)) {
            throw new InvalidArgumentException('Cannot delete a category that is assigned to assets. Deactivate it instead.');
        }

        $this->transaction(function () use ($category): void {
            $this->activityLogger->log('asset_category.deleted', $category, 'Asset category deleted', [
                'name' => $category->name,
            ]);

            $this->categories->delete($category);
        });
    }

    public function assign(AssetCategory $category, Asset $asset): Asset
    {
        if (! $category->is_active) {
            throw new InvalidArgumentException('Inactive categories cannot receive new asset assignments.');
        }

        if ($asset->asset_category_id === $category->id) {
            throw new InvalidArgumentException('This asset is already assigned to this category.');
        }

        return $this->transaction(function () use ($category, $asset): Asset {
            $previousCategoryId = $asset->asset_category_id;
            $asset->update(['asset_category_id' => $category->id]);

            $this->activityLogger->log('asset_category.assigned', $asset, 'Asset assigned to category', [
                'asset_code' => $asset->asset_code,
                'category' => $category->name,
                'previous_category_id' => $previousCategoryId,
            ]);

            return $asset->refresh();
        });
    }
}
