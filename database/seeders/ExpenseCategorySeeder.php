<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('gym.expense_categories', []) as $index => $name) {
            ExpenseCategory::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
