<?php

namespace Database\Seeders;

use App\Models\InvestmentCategory;
use Illuminate\Database\Seeder;

class InvestmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('gym.investment_categories', []) as $index => $name) {
            InvestmentCategory::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
