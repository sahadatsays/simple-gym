<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'type' => fake()->randomElement(PaymentType::cases()),
            'status' => PaymentStatus::Completed,
            'amount' => fake()->randomFloat(2, 10, 500),
            'paid_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_method' => fake()->randomElement(['cash', 'card', 'mobile_banking']),
            'reference' => fake()->optional()->bothify('REF-####'),
            'notes' => null,
        ];
    }

    public function membership(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentType::Membership,
        ]);
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentType::Product,
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_at' => now(),
        ]);
    }

    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_at' => fake()->dateTimeBetween(now()->startOfMonth(), now()),
        ]);
    }
}
