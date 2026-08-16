<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Required data for every environment.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GymSettingSeeder::class,
            CategorySeeder::class,
            InvestmentCategorySeeder::class,
            ExpenseCategorySeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
