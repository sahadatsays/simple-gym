<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 1000, 250000);

        return [
            'asset_code' => 'AST-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => fake()->words(3, true),
            'asset_category_id' => AssetCategory::factory(),
            'purchased_at' => fake()->dateTimeBetween('-3 years', 'now'),
            'purchase_price' => $purchasePrice,
            'current_value' => $purchasePrice,
            'supplier' => fake()->optional()->company(),
            'location' => fake()->optional()->randomElement(['Cardio Zone', 'Weight Room', 'Reception', 'Locker Room']),
            'condition' => AssetCondition::New,
            'status' => AssetStatus::Active,
            'warranty_expires_at' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
