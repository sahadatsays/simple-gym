<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\PaymentType;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $planIds = MembershipPlan::query()->pluck('id');

        Member::factory()->count(40)->create()->each(function (Member $member) use ($planIds): void {
            if ($planIds->isNotEmpty()) {
                $member->update(['membership_plan_id' => $planIds->random()]);
            }
        });

        Member::factory()->count(12)->expired()->create()->each(function (Member $member) use ($planIds): void {
            if ($planIds->isNotEmpty()) {
                $member->update(['membership_plan_id' => $planIds->random()]);
            }
        });

        Product::factory()->count(15)->create();
        Product::factory()->count(4)->lowStock()->create();

        $members = Member::query()->get();

        foreach ($members as $member) {
            Payment::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($member)
                ->create();
        }

        Payment::factory()->count(8)->posSale()->thisMonth()->create([
            'member_id' => null,
        ]);

        Payment::factory()->count(5)->today()->create([
            'type' => PaymentType::MembershipFee,
        ]);

        Payment::factory()->count(3)->posSale()->today()->create([
            'member_id' => null,
        ]);

        Member::factory()->count(3)->create([
            'joined_at' => now()->subDays(fake()->numberBetween(1, 14)),
            'status' => MemberStatus::Active,
            'membership_expires_at' => now()->addMonths(6),
            'membership_plan_id' => $planIds->first(),
        ]);
    }
}
