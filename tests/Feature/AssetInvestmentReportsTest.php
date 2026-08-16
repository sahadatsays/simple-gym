<?php

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->investmentCategory = InvestmentCategory::factory()->create(['name' => 'Equipment']);
    $this->assetCategory = AssetCategory::factory()->create(['name' => 'Fitness Equipment']);
});

it('shows asset and investment reports on the reports hub', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Investment Report')
        ->assertSee('Asset Report')
        ->assertSee('Maintenance Report')
        ->assertSee('Asset Value Summary');
});

it('shows investment report with filters and totals', function () {
    Investment::factory()->create([
        'investment_category_id' => $this->investmentCategory->id,
        'investment_number' => 'INV-REPORT-001',
        'invested_at' => now(),
        'amount' => 25000,
        'description' => 'Equipment funding',
    ]);

    Investment::factory()->create([
        'investment_category_id' => $this->investmentCategory->id,
        'investment_number' => 'INV-OLD-001',
        'invested_at' => now()->subMonths(2),
        'amount' => 10000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'investments',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'search' => 'INV-REPORT-001',
        ]))
        ->assertSuccessful()
        ->assertSee('Investment Report')
        ->assertSee('INV-REPORT-001')
        ->assertSee('Equipment funding')
        ->assertSee('25,000')
        ->assertDontSee('INV-OLD-001');
});

it('shows asset report with category and status filters', function () {
    Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'name' => 'Visible Treadmill',
        'asset_code' => 'AST-VISIBLE',
        'purchased_at' => now(),
        'purchase_price' => 50000,
        'current_value' => 45000,
        'status' => AssetStatus::Active,
        'location' => 'Cardio Zone',
    ]);

    Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'name' => 'Hidden Bike',
        'asset_code' => 'AST-HIDDEN',
        'purchased_at' => now(),
        'status' => AssetStatus::Sold,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'assets',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'status' => AssetStatus::Active->value,
            'search' => 'Visible',
        ]))
        ->assertSuccessful()
        ->assertSee('Asset Report')
        ->assertSee('Visible Treadmill')
        ->assertSee('Cardio Zone')
        ->assertDontSee('Hidden Bike');
});

it('shows maintenance report with maintenance type filter', function () {
    $asset = Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'name' => 'Service Treadmill',
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $asset->id,
        'maintained_at' => now(),
        'type' => AssetMaintenanceType::Preventive,
        'cost' => 1500,
        'service_provider' => 'FitPro Ltd',
        'next_maintenance_at' => now()->addMonths(6),
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $asset->id,
        'maintained_at' => now(),
        'type' => AssetMaintenanceType::Corrective,
        'cost' => 500,
        'service_provider' => 'Other Co',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'asset-maintenance',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'maintenance_type' => AssetMaintenanceType::Preventive->value,
        ]))
        ->assertSuccessful()
        ->assertSee('Maintenance Report')
        ->assertSee('FitPro Ltd')
        ->assertSee('1,500')
        ->assertDontSee('Other Co');
});

it('shows asset value summary totals', function () {
    Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'purchased_at' => now()->subMonths(2),
        'purchase_price' => 50000,
        'current_value' => 40000,
        'status' => AssetStatus::Active,
    ]);

    $recentAsset = Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'purchased_at' => now(),
        'purchase_price' => 15000,
        'current_value' => 15000,
        'status' => AssetStatus::Active,
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $recentAsset->id,
        'maintained_at' => now(),
        'cost' => 2500,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'asset-value-summary',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertSee('Asset Value Summary')
        ->assertSee('Total Purchase Value')
        ->assertSee('Current Asset Value')
        ->assertSee('Total Maintenance Cost')
        ->assertSee('15,000')
        ->assertSee('55,000')
        ->assertSee('2,500');
});

it('exports investment report as csv and pdf', function () {
    Investment::factory()->create([
        'investment_category_id' => $this->investmentCategory->id,
        'invested_at' => now(),
        'amount' => 5000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'investments',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'excel',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'investments',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'pdf',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('shows print friendly asset report view', function () {
    Asset::factory()->create([
        'asset_category_id' => $this->assetCategory->id,
        'purchased_at' => now(),
        'purchase_price' => 10000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'assets',
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'export' => 'print',
        ]))
        ->assertSuccessful()
        ->assertSee('Print Report')
        ->assertSee('Asset Report');
});

it('denies asset and investment reports without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.reports.show', ['report' => 'investments']))
        ->assertForbidden();
});

it('allows general reports for users with reports view only', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('Daily Collection')
        ->assertDontSee('Investment Report');
});
