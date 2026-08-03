<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 5, 150);
        $sellingPrice = $purchasePrice + fake()->randomFloat(2, 5, 80);

        return [
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => fake()->unique()->numerify('############'),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Supplements', 'Apparel', 'Accessories', 'Equipment']),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'stock' => fake()->numberBetween(0, 100),
            'minimum_stock' => 5,
            'status' => ProductStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Inactive,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(0, 5),
            'minimum_stock' => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
