<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\MembershipRenewal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipRenewal>
 */
class MembershipRenewalFactory extends Factory
{
    protected $model = MembershipRenewal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'membership_plan_id' => MembershipPlan::factory(),
            'invoice_id' => Invoice::factory(),
            'previous_expires_at' => now()->subDays(5),
            'new_expires_at' => now()->addDays(25),
            'renewed_at' => now(),
        ];
    }
}
