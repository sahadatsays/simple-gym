<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('gym.product_categories', []) as $index => $name) {
            Category::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
