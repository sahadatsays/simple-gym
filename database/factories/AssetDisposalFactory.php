<?php

namespace Database\Factories;

use App\Enums\AssetDisposalType;
use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDisposal>
 */
class AssetDisposalFactory extends Factory
{
    protected $model = AssetDisposal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $disposalType = fake()->randomElement(AssetDisposalType::cases());

        return [
            'asset_id' => Asset::factory(),
            'disposed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'disposal_type' => $disposalType,
            'sale_amount' => $disposalType === AssetDisposalType::Sold
                ? fake()->randomFloat(2, 1000, 50000)
                : null,
            'buyer' => fake()->optional()->company(),
            'reason' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function sold(): static
    {
        return $this->state(fn (): array => [
            'disposal_type' => AssetDisposalType::Sold,
            'sale_amount' => fake()->randomFloat(2, 1000, 50000),
            'buyer' => fake()->company(),
        ]);
    }
}
