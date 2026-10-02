<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BorrowingRepayment>
 */
class BorrowingRepaymentFactory extends Factory
{
    protected $model = BorrowingRepayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repayment_no' => 'RPY-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'borrowing_id' => Borrowing::factory()->state(['amount' => 5000]),
            'repayment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'description' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
