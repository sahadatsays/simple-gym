<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Demo data for local development and testing previews.
     */
    public function run(): void
    {
        $this->call([
            MembershipPlanSeeder::class,
            DemoUserSeeder::class,
            DashboardSeeder::class,
        ]);
    }
}
