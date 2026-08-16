<?php

namespace App\Services;

use App\Models\Category;
use App\Support\ActivityLogger;
use InvalidArgumentException;

class CategoryService extends BaseService
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return $this->transaction(function () use ($data): Category {
            $category = Category::query()->create($data);

            $this->activityLogger->log('category.created', $category, 'Category created', [
                'name' => $category->name,
            ]);

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        return $this->transaction(function () use ($category, $data): Category {
            $category->update($data);

            $this->activityLogger->log('category.updated', $category, 'Category updated', [
                'name' => $category->name,
            ]);

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        $this->transaction(function () use ($category): void {
            if ($category->products()->exists()) {
                throw new InvalidArgumentException('Cannot delete a category that has products assigned. Reassign or remove those products first.');
            }

            $this->activityLogger->log('category.deleted', $category, 'Category deleted', [
                'name' => $category->name,
            ]);

            $category->delete();
        });
    }
}
