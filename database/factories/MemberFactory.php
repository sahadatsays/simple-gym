<?php

namespace Database\Factories;

use App\Enums\Gender;
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
            'rfid_card' => fake()->unique()->numerify('RFID########'),
            'photo_path' => null,
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->unique()->numerify('01#########'),
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'address' => fake()->optional()->address(),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->numerify('01#########'),
            'membership_plan_id' => null,
            'joined_at' => $joinedAt,
            'membership_expires_at' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => MemberStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'membership_expires_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'status' => MemberStatus::Expired,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'membership_expires_at' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => MemberStatus::Active,
        ]);
    }
}
