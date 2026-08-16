<?php

use App\Enums\AssetCondition;
use App\Enums\AssetDisposalType;
use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Enums\PaymentMethod;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates asset and investment tables with expected columns', function () {
    expect(Schema::hasTable('investment_categories'))->toBeTrue()
        ->and(Schema::hasColumns('investment_categories', ['name', 'description', 'is_active', 'sort_order']))->toBeTrue()
        ->and(Schema::hasTable('investments'))->toBeTrue()
        ->and(Schema::hasColumns('investments', [
            'investment_number',
            'invested_at',
            'investment_category_id',
            'amount',
            'payment_method',
            'description',
            'attachment_path',
            'created_by',
            'deleted_at',
        ]))->toBeTrue()
        ->and(Schema::hasTable('asset_categories'))->toBeTrue()
        ->and(Schema::hasTable('assets'))->toBeTrue()
        ->and(Schema::hasColumns('assets', [
            'asset_code',
            'name',
            'asset_category_id',
            'purchased_at',
            'purchase_price',
            'current_value',
            'supplier',
            'location',
            'condition',
            'status',
            'warranty_expires_at',
            'notes',
            'created_by',
            'deleted_at',
        ]))->toBeTrue()
        ->and(Schema::hasTable('asset_maintenances'))->toBeTrue()
        ->and(Schema::hasTable('asset_disposals'))->toBeTrue();
});

it('persists asset and investment records with relationships', function () {
    $user = User::factory()->create();

    $investmentCategory = InvestmentCategory::query()->create([
        'name' => 'Equipment',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $investment = Investment::query()->create([
        'investment_number' => 'INV-001',
        'invested_at' => '2026-08-01',
        'investment_category_id' => $investmentCategory->id,
        'amount' => 50000,
        'payment_method' => PaymentMethod::Bank,
        'description' => 'Treadmill purchase',
        'created_by' => $user->id,
    ]);

    $assetCategory = AssetCategory::query()->create([
        'name' => 'Fitness Equipment',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $asset = Asset::query()->create([
        'asset_code' => 'AST-001',
        'name' => 'Commercial Treadmill',
        'asset_category_id' => $assetCategory->id,
        'purchased_at' => '2026-08-01',
        'purchase_price' => 50000,
        'current_value' => 45000,
        'supplier' => 'FitPro Ltd',
        'location' => 'Cardio Zone',
        'condition' => AssetCondition::Good,
        'status' => AssetStatus::Active,
        'created_by' => $user->id,
    ]);

    $maintenance = AssetMaintenance::query()->create([
        'asset_id' => $asset->id,
        'maintained_at' => '2026-08-15',
        'type' => AssetMaintenanceType::Preventive,
        'cost' => 1500,
        'service_provider' => 'FitPro Ltd',
        'description' => 'Belt lubrication',
        'next_maintenance_at' => '2027-02-15',
        'created_by' => $user->id,
    ]);

    $disposal = AssetDisposal::query()->create([
        'asset_id' => $asset->id,
        'disposed_at' => '2028-01-01',
        'disposal_type' => AssetDisposalType::Sold,
        'sale_amount' => 20000,
        'buyer' => 'Second Hand Gym',
        'reason' => 'Upgrade',
        'created_by' => $user->id,
    ]);

    expect($investment->category->name)->toBe('Equipment')
        ->and($investment->creator->is($user))->toBeTrue()
        ->and($asset->category->name)->toBe('Fitness Equipment')
        ->and($asset->maintenances)->toHaveCount(1)
        ->and($asset->maintenances->first()->is($maintenance))->toBeTrue()
        ->and($asset->disposal->is($disposal))->toBeTrue()
        ->and($maintenance->asset->is($asset))->toBeTrue()
        ->and($disposal->asset->is($asset))->toBeTrue();
});

it('soft deletes investments and assets', function () {
    $investmentCategory = InvestmentCategory::query()->create([
        'name' => 'Renovation',
        'is_active' => true,
    ]);

    $investment = Investment::query()->create([
        'investment_number' => 'INV-002',
        'invested_at' => '2026-08-02',
        'investment_category_id' => $investmentCategory->id,
        'amount' => 10000,
        'payment_method' => PaymentMethod::Cash,
    ]);

    $assetCategory = AssetCategory::query()->create([
        'name' => 'Furniture',
        'is_active' => true,
    ]);

    $asset = Asset::query()->create([
        'asset_code' => 'AST-002',
        'name' => 'Reception Desk',
        'asset_category_id' => $assetCategory->id,
        'purchased_at' => '2026-08-02',
        'purchase_price' => 10000,
        'status' => AssetStatus::Active,
    ]);

    $investment->delete();
    $asset->delete();

    expect(Investment::query()->count())->toBe(0)
        ->and(Investment::withTrashed()->count())->toBe(1)
        ->and(Asset::query()->count())->toBe(0)
        ->and(Asset::withTrashed()->count())->toBe(1);
});
