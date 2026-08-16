<?php

namespace App\Services;

use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Models\ExpenseCategory;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class ExpenseCategoryService extends BaseService
{
    public function __construct(
        private ExpenseCategoryRepositoryInterface $categories,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): ExpenseCategory
    {
        return $this->transaction(function () use ($data, $createdBy): ExpenseCategory {
            $payload = $data;
            $payload['created_by'] = $createdBy;

            $category = $this->categories->create($payload);

            $this->activityLogger->log('expense_category.created', $category, 'Expense category created', [
                'name' => $category->name,
            ]);

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        return $this->transaction(function () use ($category, $data): ExpenseCategory {
            $updatedCategory = $this->categories->update($category, $data);

            $this->activityLogger->log('expense_category.updated', $updatedCategory, 'Expense category updated', [
                'name' => $updatedCategory->name,
            ]);

            return $updatedCategory;
        });
    }

    public function delete(ExpenseCategory $category): void
    {
        if ($this->categories->hasExpenses($category)) {
            throw new InvalidArgumentException('Cannot delete a category that is used by expenses.');
        }

        $this->transaction(function () use ($category): void {
            $this->activityLogger->log('expense_category.deleted', $category, 'Expense category deleted', [
                'name' => $category->name,
            ]);

            $this->categories->delete($category);
        });
    }
}
