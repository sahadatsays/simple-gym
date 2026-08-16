<?php

namespace Database\Factories;

use App\Enums\AssetMaintenanceType;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMaintenance>
 */
class AssetMaintenanceFactory extends Factory
{
    protected $model = AssetMaintenance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maintainedAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'asset_id' => Asset::factory(),
            'maintained_at' => $maintainedAt,
            'type' => fake()->randomElement(AssetMaintenanceType::cases()),
            'cost' => fake()->optional()->randomFloat(2, 100, 10000),
            'service_provider' => fake()->optional()->company(),
            'description' => fake()->optional()->sentence(),
            'next_maintenance_at' => fake()->optional()->dateTimeBetween($maintainedAt, '+1 year'),
            'attachment_path' => null,
            'created_by' => null,
        ];
    }
}
