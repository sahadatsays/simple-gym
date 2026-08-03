<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSale>
 */
class ProductSaleFactory extends Factory
{
    protected $model = ProductSale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 200);
        $unitCost = $unitPrice * fake()->randomFloat(2, 0.4, 0.8);
        $lineTotal = round($quantity * $unitPrice, 2);

        return [
            'product_id' => Product::factory(),
            'invoice_id' => Invoice::factory(),
            'payment_id' => Payment::factory(),
            'member_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_cost' => round($unitCost, 2),
            'line_discount' => 0,
            'line_total' => $lineTotal,
            'sold_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function forMember(Member $member): static
    {
        return $this->state(fn (array $attributes) => [
            'member_id' => $member->id,
        ]);
    }
}
