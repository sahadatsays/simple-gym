<?php

namespace App\Services;

use App\Contracts\Repositories\InvestmentCategoryRepositoryInterface;
use App\Models\InvestmentCategory;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class InvestmentCategoryService extends BaseService
{
    public function __construct(
        private InvestmentCategoryRepositoryInterface $categories,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InvestmentCategory
    {
        return $this->transaction(function () use ($data): InvestmentCategory {
            $category = $this->categories->create($data);

            $this->activityLogger->log('investment_category.created', $category, 'Investment category created', [
                'name' => $category->name,
            ]);

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InvestmentCategory $category, array $data): InvestmentCategory
    {
        return $this->transaction(function () use ($category, $data): InvestmentCategory {
            $updatedCategory = $this->categories->update($category, $data);

            $this->activityLogger->log('investment_category.updated', $updatedCategory, 'Investment category updated', [
                'name' => $updatedCategory->name,
            ]);

            return $updatedCategory;
        });
    }

    public function delete(InvestmentCategory $category): void
    {
        if ($this->categories->hasInvestments($category)) {
            throw new InvalidArgumentException('Cannot delete a category that is used by investments.');
        }

        $this->transaction(function () use ($category): void {
            $this->activityLogger->log('investment_category.deleted', $category, 'Investment category deleted', [
                'name' => $category->name,
            ]);

            $this->categories->delete($category);
        });
    }
}
