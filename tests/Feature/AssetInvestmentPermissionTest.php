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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->investmentCategory = InvestmentCategory::query()->create([
        'name' => 'Equipment',
        'is_active' => true,
    ]);

    $this->investment = Investment::query()->create([
        'investment_number' => 'INV-001',
        'invested_at' => '2026-08-01',
        'investment_category_id' => $this->investmentCategory->id,
        'amount' => 50000,
        'payment_method' => PaymentMethod::Cash,
    ]);

    $this->assetCategory = AssetCategory::query()->create([
        'name' => 'Fitness Equipment',
        'is_active' => true,
    ]);

    $this->asset = Asset::query()->create([
        'asset_code' => 'AST-001',
        'name' => 'Treadmill',
        'asset_category_id' => $this->assetCategory->id,
        'purchased_at' => '2026-08-01',
        'purchase_price' => 50000,
        'status' => AssetStatus::Active,
        'condition' => AssetCondition::Good,
    ]);

    $this->maintenance = AssetMaintenance::query()->create([
        'asset_id' => $this->asset->id,
        'maintained_at' => '2026-08-15',
        'type' => AssetMaintenanceType::Preventive,
        'cost' => 1500,
    ]);

    $this->disposal = AssetDisposal::query()->create([
        'asset_id' => $this->asset->id,
        'disposed_at' => '2028-01-01',
        'disposal_type' => AssetDisposalType::Sale,
        'sale_amount' => 20000,
    ]);
});

function createUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo($permissions);

    return $user;
}

it('syncs asset and investment permissions through the seeder', function () {
    $permissions = [
        'investments.view', 'investments.create', 'investments.edit', 'investments.delete',
        'assets.view', 'assets.create', 'assets.edit', 'assets.delete',
        'asset-categories.view', 'asset-categories.create', 'asset-categories.edit', 'asset-categories.delete',
        'asset-maintenances.view', 'asset-maintenances.create', 'asset-maintenances.edit', 'asset-maintenances.delete',
        'asset-disposals.view', 'asset-disposals.create',
        'asset-investment-reports.view',
    ];

    foreach ($permissions as $permission) {
        expect(Permission::query()->where('name', $permission)->exists())->toBeTrue();
    }
});

it('authorizes investment actions through the investment policy', function () {
    $viewer = createUserWithPermissions(['investments.view']);
    $editor = createUserWithPermissions(['investments.view', 'investments.edit']);
    $creator = createUserWithPermissions(['investments.create']);
    $deleter = createUserWithPermissions(['investments.delete']);

    expect(Gate::forUser($viewer)->allows('viewAny', Investment::class))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('view', $this->investment))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('create', Investment::class))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('update', $this->investment))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('create', Investment::class))->toBeTrue()
        ->and(Gate::forUser($deleter)->allows('delete', $this->investment))->toBeTrue();
});

it('authorizes investment categories through the investment category policy', function () {
    $viewer = createUserWithPermissions(['investments.view']);
    $creator = createUserWithPermissions(['investments.create']);
    $editor = createUserWithPermissions(['investments.edit']);
    $deleter = createUserWithPermissions(['investments.delete']);

    expect(Gate::forUser($viewer)->allows('viewAny', InvestmentCategory::class))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('create', InvestmentCategory::class))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('update', $this->investmentCategory))->toBeTrue()
        ->and(Gate::forUser($deleter)->allows('delete', $this->investmentCategory))->toBeTrue();
});

it('authorizes asset and asset category actions through their policies', function () {
    $assetViewer = createUserWithPermissions(['assets.view']);
    $assetEditor = createUserWithPermissions(['assets.edit']);
    $categoryManager = createUserWithPermissions([
        'asset-categories.view',
        'asset-categories.create',
        'asset-categories.edit',
        'asset-categories.delete',
    ]);

    expect(Gate::forUser($assetViewer)->allows('view', $this->asset))->toBeTrue()
        ->and(Gate::forUser($assetEditor)->allows('update', $this->asset))->toBeTrue()
        ->and(Gate::forUser($categoryManager)->allows('viewAny', AssetCategory::class))->toBeTrue()
        ->and(Gate::forUser($categoryManager)->allows('delete', $this->assetCategory))->toBeTrue();
});

it('authorizes maintenance and disposal actions through their policies', function () {
    $maintenanceManager = createUserWithPermissions([
        'asset-maintenances.view',
        'asset-maintenances.create',
        'asset-maintenances.edit',
        'asset-maintenances.delete',
    ]);

    $disposalManager = createUserWithPermissions([
        'asset-disposals.view',
        'asset-disposals.create',
    ]);

    expect(Gate::forUser($maintenanceManager)->allows('update', $this->maintenance))->toBeTrue()
        ->and(Gate::forUser($maintenanceManager)->allows('delete', $this->maintenance))->toBeTrue()
        ->and(Gate::forUser($disposalManager)->allows('view', $this->disposal))->toBeTrue()
        ->and(Gate::forUser($disposalManager)->allows('create', AssetDisposal::class))->toBeTrue()
        ->and(Gate::forUser($disposalManager)->allows('update', $this->disposal))->toBeFalse()
        ->and(Gate::forUser($disposalManager)->allows('delete', $this->disposal))->toBeFalse();
});

it('shows asset and investment permission labels on the role form', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super-admin');

    $response = $this->actingAs($admin)->get(route('admin.roles.create'));

    $response->assertSuccessful()
        ->assertSee('investments.view', false)
        ->assertSee('asset-maintenances.create', false)
        ->assertSee('asset-investment-reports.view', false);
});
