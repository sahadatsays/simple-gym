<?php

namespace Database\Seeders;

use App\Enums\PlanStatus;
use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Monthly Basic',
                'duration_days' => 30,
                'admission_fee' => 500,
                'membership_fee' => 1500,
                'description' => 'Standard gym access for one month.',
                'status' => PlanStatus::Active,
                'features' => ['Gym floor access', 'Locker room', 'Basic equipment'],
            ],
            [
                'name' => 'Quarterly Pro',
                'duration_days' => 90,
                'admission_fee' => 500,
                'membership_fee' => 4000,
                'description' => 'Three months with group class access.',
                'status' => PlanStatus::Active,
                'features' => ['Gym floor access', 'Group classes', 'Locker room', 'Sauna access'],
            ],
            [
                'name' => 'Annual Elite',
                'duration_days' => 365,
                'admission_fee' => 1000,
                'membership_fee' => 12000,
                'description' => 'Full-year membership with premium perks.',
                'status' => PlanStatus::Active,
                'features' => ['All-access pass', 'Personal trainer session', 'Group classes', 'Sauna access', 'Guest pass (2/month)'],
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::query()->updateOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}
