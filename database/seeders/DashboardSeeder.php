<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\PaymentType;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        Member::factory()->count(40)->create();
        Member::factory()->count(12)->expired()->create();

        Product::factory()->count(15)->create();
        Product::factory()->count(4)->lowStock()->create();

        $members = Member::query()->get();

        foreach ($members as $member) {
            Payment::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($member)
                ->create();
        }

        Payment::factory()->count(8)->product()->thisMonth()->create([
            'member_id' => null,
        ]);

        Payment::factory()->count(5)->today()->create([
            'type' => PaymentType::Membership,
        ]);

        Payment::factory()->count(3)->product()->today()->create([
            'member_id' => null,
        ]);

        Member::factory()->count(3)->create([
            'joined_at' => now()->subDays(fake()->numberBetween(1, 14)),
            'status' => MemberStatus::Active,
            'membership_expires_at' => now()->addMonths(6),
        ]);
    }
}
