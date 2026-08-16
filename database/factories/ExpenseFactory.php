<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_number' => 'EXP-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'expensed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'expense_category_id' => ExpenseCategory::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'paid_to' => fake()->optional()->company(),
            'description' => fake()->optional()->sentence(),
            'attachment_path' => null,
            'status' => ExpenseStatus::Paid,
            'created_by' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Cancelled,
        ]);
    }
}
