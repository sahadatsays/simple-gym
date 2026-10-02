<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
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
});

it('lists asset categories for authorized users', function () {
    AssetCategory::factory()->create([
        'name' => 'Fitness Equipment',
        'description' => 'Machines and free weights',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-categories.index'))
        ->assertSuccessful()
        ->assertSee('Asset Categories')
        ->assertSee('Fitness Equipment')
        ->assertSee('Machines and free weights');
});

it('filters asset categories by search and status', function () {
    AssetCategory::factory()->create([
        'name' => 'Cardio Machines',
        'is_active' => true,
    ]);

    AssetCategory::factory()->inactive()->create([
        'name' => 'Retired Equipment',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-categories.index', [
            'search' => 'Cardio',
            'status' => 'active',
        ]))
        ->assertSuccessful()
        ->assertSee('Cardio Machines')
        ->assertDontSee('Retired Equipment');
});

it('creates an asset category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.asset-categories.store'), [
            'name' => 'Furniture',
            'description' => 'Reception and office furniture',
            'is_active' => true,
            'sort_order' => 4,
        ])
        ->assertRedirect(route('admin.asset-categories.index'));

    $category = AssetCategory::query()->where('name', 'Furniture')->first();

    expect($category)->not->toBeNull()
        ->and($category->description)->toBe('Reception and office furniture')
        ->and($category->sort_order)->toBe(4)
        ->and($category->created_by)->toBe($this->admin->id);
});

it('updates an asset category', function () {
    $category = AssetCategory::factory()->create(['name' => 'Old Category']);

    $this->actingAs($this->admin)
        ->put(route('admin.asset-categories.update', $category), [
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'is_active' => false,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('admin.asset-categories.index'));

    expect($category->fresh())
        ->name->toBe('Updated Category')
        ->is_active->toBeFalse()
        ->sort_order->toBe(3);
});

it('prevents deleting a category assigned to assets', function () {
    $category = AssetCategory::factory()->create();
    Asset::factory()->create(['asset_category_id' => $category->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.asset-categories.index'))
        ->delete(route('admin.asset-categories.destroy', $category))
        ->assertRedirect(route('admin.asset-categories.index'))
        ->assertSessionHasErrors('category');

    expect(AssetCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('prevents deleting a category assigned to soft-deleted assets', function () {
    $category = AssetCategory::factory()->create();
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);
    $asset->delete();

    $this->actingAs($this->admin)
        ->from(route('admin.asset-categories.index'))
        ->delete(route('admin.asset-categories.destroy', $category))
        ->assertRedirect(route('admin.asset-categories.index'))
        ->assertSessionHasErrors('category');

    expect(AssetCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('deletes an unused asset category', function () {
    $category = AssetCategory::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.asset-categories.destroy', $category))
        ->assertRedirect(route('admin.asset-categories.index'));

    expect(AssetCategory::query()->whereKey($category->id)->exists())->toBeFalse();
});

it('validates unique category names', function () {
    AssetCategory::factory()->create(['name' => 'Unique Category']);

    $this->actingAs($this->admin)
        ->from(route('admin.asset-categories.create'))
        ->post(route('admin.asset-categories.store'), [
            'name' => 'Unique Category',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.asset-categories.create'))
        ->assertSessionHasErrors('name');
});

it('assigns an existing asset to an active category', function () {
    $currentCategory = AssetCategory::factory()->create(['name' => 'Furniture']);
    $targetCategory = AssetCategory::factory()->create(['name' => 'Cardio']);
    $asset = Asset::factory()->create([
        'asset_category_id' => $currentCategory->id,
        'name' => 'Treadmill',
        'asset_code' => 'AST-MOVE-001',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.asset-categories.assign', $targetCategory), [
            'asset_id' => $asset->id,
        ])
        ->assertRedirect(route('admin.asset-categories.show', $targetCategory));

    expect($asset->fresh()->asset_category_id)->toBe($targetCategory->id);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-categories.show', $targetCategory))
        ->assertSuccessful()
        ->assertSee('Treadmill')
        ->assertSee('AST-MOVE-001');
});

it('rejects assigning an asset to an inactive category', function () {
    $currentCategory = AssetCategory::factory()->create();
    $inactiveCategory = AssetCategory::factory()->inactive()->create();
    $asset = Asset::factory()->create(['asset_category_id' => $currentCategory->id]);

    $this->actingAs($this->admin)
        ->from(route('admin.asset-categories.show', $inactiveCategory))
        ->post(route('admin.asset-categories.assign', $inactiveCategory), [
            'asset_id' => $asset->id,
        ])
        ->assertRedirect(route('admin.asset-categories.show', $inactiveCategory))
        ->assertSessionHasErrors('asset_id');

    expect($asset->fresh()->asset_category_id)->toBe($currentCategory->id);
});

it('rejects a new asset assigned to an inactive category', function () {
    $inactiveCategory = AssetCategory::factory()->inactive()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.assets.create'))
        ->post(route('admin.assets.store'), [
            'name' => 'Broken Bench',
            'asset_category_id' => $inactiveCategory->id,
            'purchased_at' => '2026-08-16',
            'purchase_price' => 12000,
        ])
        ->assertRedirect(route('admin.assets.create'))
        ->assertSessionHasErrors('asset_category_id');

    expect(Asset::query()->where('name', 'Broken Bench')->exists())->toBeFalse();
});

it('keeps an inactive category on an existing asset but blocks switching to another inactive category', function () {
    $currentCategory = AssetCategory::factory()->inactive()->create(['name' => 'Retired']);
    $otherInactiveCategory = AssetCategory::factory()->inactive()->create(['name' => 'Archived']);
    $asset = Asset::factory()->create([
        'asset_category_id' => $currentCategory->id,
        'purchase_price' => 20000,
        'current_value' => 15000,
        'status' => AssetStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.assets.update', $asset), [
            'name' => $asset->name,
            'asset_category_id' => $currentCategory->id,
            'purchased_at' => $asset->purchased_at->toDateString(),
            'purchase_price' => 20000,
            'current_value' => 15000,
            'condition' => $asset->condition->value,
            'status' => AssetStatus::Active->value,
        ])
        ->assertRedirect(route('admin.assets.show', $asset));

    $this->actingAs($this->admin)
        ->from(route('admin.assets.edit', $asset))
        ->put(route('admin.assets.update', $asset), [
            'name' => $asset->name,
            'asset_category_id' => $otherInactiveCategory->id,
            'purchased_at' => $asset->purchased_at->toDateString(),
            'purchase_price' => 20000,
            'current_value' => 15000,
            'condition' => $asset->condition->value,
            'status' => AssetStatus::Active->value,
        ])
        ->assertRedirect(route('admin.assets.edit', $asset))
        ->assertSessionHasErrors('asset_category_id');

    expect($asset->fresh()->asset_category_id)->toBe($currentCategory->id);
});

it('shows asset category totals for the selected purchase period', function () {
    $cardio = AssetCategory::factory()->create(['name' => 'Cardio', 'sort_order' => 1]);
    $furniture = AssetCategory::factory()->create(['name' => 'Furniture', 'sort_order' => 2]);

    Asset::factory()->create([
        'asset_category_id' => $cardio->id,
        'name' => 'Visible Treadmill',
        'purchased_at' => now(),
        'purchase_price' => 50000,
        'current_value' => 45000,
        'status' => AssetStatus::Active,
    ]);

    Asset::factory()->create([
        'asset_category_id' => $cardio->id,
        'name' => 'Old Bike',
        'purchased_at' => now()->subMonths(2),
        'purchase_price' => 10000,
        'current_value' => 8000,
        'status' => AssetStatus::Active,
    ]);

    Asset::factory()->create([
        'asset_category_id' => $furniture->id,
        'purchased_at' => now(),
        'purchase_price' => 7000,
        'current_value' => 7000,
        'status' => AssetStatus::Sold,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'asset-categories',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'status' => AssetStatus::Active->value,
        ]))
        ->assertSuccessful()
        ->assertSee('Asset Category Report')
        ->assertSee('Cardio')
        ->assertSee('Furniture')
        ->assertSee('50,000')
        ->assertDontSee('10,000')
        ->assertDontSee('7,000');
});

it('forbids asset category management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $category = AssetCategory::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.asset-categories.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.asset-categories.destroy', $category))
        ->assertForbidden();
});

it('shows asset categories in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Asset Categories', false);
});
