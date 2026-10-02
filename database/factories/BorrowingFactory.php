<?php

namespace Database\Factories;

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    protected $model = Borrowing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'borrowing_no' => 'BOR-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'lender_name' => fake()->name(),
            'lender_phone' => fake()->optional()->numerify('01#########'),
            'borrowing_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'purpose' => fake()->optional()->sentence(3),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'description' => fake()->optional()->sentence(),
            'status' => BorrowingStatus::Active,
            'created_by' => null,
        ];
    }

    public function fullyRepaid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BorrowingStatus::FullyRepaid,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BorrowingStatus::Cancelled,
        ]);
    }
}
