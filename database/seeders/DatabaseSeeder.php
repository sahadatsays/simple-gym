<?php

namespace Database\Seeders;

use App\Support\SeedingMode;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (SeedingMode::shouldSeedDemoData()) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
