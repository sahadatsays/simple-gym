<?php

namespace Database\Factories;

use App\Enums\RfidCardStatus;
use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RfidCard>
 */
class RfidCardFactory extends Factory
{
    protected $model = RfidCard::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_number' => fake()->unique()->numerify('RFID########'),
            'status' => RfidCardStatus::Unassigned,
            'member_id' => null,
            'assigned_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfidCardStatus::Active,
            'member_id' => Member::factory(),
            'assigned_at' => now(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfidCardStatus::Disabled,
            'assigned_at' => now()->subMonths(2),
        ]);
    }
}
