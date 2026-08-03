<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
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
            'discount_amount' => 0,
            'paid_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases())->value,
            'reference' => fake()->optional()->bothify('REF-####'),
            'receipt_number' => 'RCP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'notes' => null,
        ];
    }

    public function membershipFee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentType::MembershipFee,
        ]);
    }

    public function posSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentType::PosSale,
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
