<?php

namespace Database\Factories;

use App\Enums\LockerStatus;
use App\Models\Locker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locker>
 */
class LockerFactory extends Factory
{
    protected $model = Locker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'locker_number' => 'L-'.fake()->unique()->numerify('###'),
            'location' => fake()->optional()->randomElement(['Ground floor', 'First floor', 'Men\'s area', 'Women\'s area']),
            'category' => fake()->optional()->randomElement(['Standard', 'Large', 'Premium']),
            'monthly_fee' => 0,
            'status' => LockerStatus::Available,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LockerStatus::Reserved,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LockerStatus::Maintenance,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LockerStatus::Disabled,
        ]);
    }
}
