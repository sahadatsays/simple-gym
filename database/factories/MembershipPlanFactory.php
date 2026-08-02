<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'duration_days' => fake()->randomElement([30, 90, 180, 365]),
            'admission_fee' => fake()->randomFloat(2, 0, 500),
            'membership_fee' => fake()->randomFloat(2, 500, 5000),
            'description' => fake()->optional()->sentence(),
            'status' => PlanStatus::Active,
            'features' => fake()->randomElements([
                'Gym access',
                'Locker room',
                'Personal trainer session',
                'Group classes',
                'Sauna access',
            ], fake()->numberBetween(1, 4)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PlanStatus::Inactive,
        ]);
    }
}
