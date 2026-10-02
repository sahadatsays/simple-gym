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
            'card_fee' => 0,
            'deposit_amount' => 0,
            'status' => RfidCardStatus::Available,
            'member_id' => null,
            'assigned_at' => null,
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfidCardStatus::Assigned,
            'member_id' => Member::factory(),
            'assigned_at' => now(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfidCardStatus::Blocked,
            'assigned_at' => now()->subMonths(2),
        ]);
    }
}
