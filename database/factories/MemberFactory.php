<?php

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinedAt = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'member_code' => 'M'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'joined_at' => $joinedAt,
            'membership_expires_at' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => MemberStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_expires_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'status' => MemberStatus::Expired,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_expires_at' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => MemberStatus::Active,
        ]);
    }
}
