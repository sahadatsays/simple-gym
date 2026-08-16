<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investment>
 */
class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'investment_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'invested_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'investment_category_id' => InvestmentCategory::factory(),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'description' => fake()->optional()->sentence(),
            'attachment_path' => null,
            'created_by' => null,
        ];
    }
}
