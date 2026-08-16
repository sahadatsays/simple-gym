<?php

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use App\Repositories\AssetRepository;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->category = AssetCategory::factory()->create(['name' => 'Fitness Equipment']);
});

it('lists assets for authorized users', function () {
    Asset::factory()->create([
        'name' => 'Commercial Treadmill',
        'asset_category_id' => $this->category->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.assets.index'))
        ->assertSuccessful()
        ->assertSee('Assets')
        ->assertSee('Commercial Treadmill');
});

it('filters assets by search, category, status, condition, and location', function () {
    $otherCategory = AssetCategory::factory()->create(['name' => 'Furniture']);

    Asset::factory()->create([
        'name' => 'Visible Treadmill',
        'asset_category_id' => $this->category->id,
        'status' => AssetStatus::Active,
        'condition' => AssetCondition::New,
        'location' => 'Cardio Zone',
    ]);

    Asset::factory()->create([
        'name' => 'Hidden Desk',
        'asset_category_id' => $otherCategory->id,
        'status' => AssetStatus::Inactive,
        'condition' => AssetCondition::Fair,
        'location' => 'Reception',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.assets.index', [
            'search' => 'Treadmill',
            'asset_category_id' => $this->category->id,
            'status' => AssetStatus::Active->value,
            'condition' => AssetCondition::New->value,
            'location' => 'Cardio Zone',
        ]))
        ->assertSuccessful()
        ->assertSee('Visible Treadmill')
        ->assertDontSee('Hidden Desk');
});

it('creates an asset with auto-generated code and defaults', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.assets.store'), [
            'name' => 'Leg Press Machine',
            'asset_category_id' => $this->category->id,
            'purchased_at' => '2026-08-16',
            'purchase_price' => 85000,
            'supplier' => 'FitPro Ltd',
            'location' => 'Weight Room',
        ]);

    $asset = Asset::query()->first();

    expect($asset)->not->toBeNull()
        ->and($asset->asset_code)->toStartWith('AST-'.now()->format('Ymd'))
        ->and((float) $asset->purchase_price)->toBe(85000.0)
        ->and((float) $asset->current_value)->toBe(85000.0)
        ->and($asset->status)->toBe(AssetStatus::Active)
        ->and($asset->condition)->toBe(AssetCondition::New)
        ->and($asset->created_by)->toBe($this->admin->id);

    $response->assertRedirect(route('admin.assets.show', $asset));
});

it('shows asset details', function () {
    $asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'created_by' => $this->admin->id,
        'notes' => 'Requires monthly servicing',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.assets.show', $asset))
        ->assertSuccessful()
        ->assertSee($asset->asset_code)
        ->assertSee('Requires monthly servicing')
        ->assertSee($this->admin->name);
});

it('updates an asset', function () {
    $asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'purchase_price' => 50000,
        'current_value' => 50000,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.assets.update', $asset), [
            'name' => 'Updated Asset Name',
            'asset_category_id' => $this->category->id,
            'purchased_at' => '2026-08-10',
            'purchase_price' => 50000,
            'current_value' => 42000,
            'condition' => AssetCondition::Good->value,
            'status' => AssetStatus::UnderMaintenance->value,
            'location' => 'Locker Room',
            'notes' => 'Sent for repair',
        ])
        ->assertRedirect(route('admin.assets.show', $asset));

    expect($asset->fresh())
        ->name->toBe('Updated Asset Name')
        ->condition->toBe(AssetCondition::Good)
        ->status->toBe(AssetStatus::UnderMaintenance)
        ->location->toBe('Locker Room');
});

it('soft deletes an asset', function () {
    $asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.assets.destroy', $asset))
        ->assertRedirect(route('admin.assets.index'));

    expect(Asset::query()->count())->toBe(0)
        ->and(Asset::withTrashed()->count())->toBe(1);
});

it('validates purchase price must be greater than zero', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.assets.create'))
        ->post(route('admin.assets.store'), [
            'name' => 'Invalid Asset',
            'asset_category_id' => $this->category->id,
            'purchased_at' => '2026-08-16',
            'purchase_price' => 0,
        ])
        ->assertRedirect(route('admin.assets.create'))
        ->assertSessionHasErrors('purchase_price');
});

it('validates current value cannot be negative', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.assets.create'))
        ->post(route('admin.assets.store'), [
            'name' => 'Invalid Asset',
            'asset_category_id' => $this->category->id,
            'purchased_at' => '2026-08-16',
            'purchase_price' => 10000,
            'current_value' => -1,
        ])
        ->assertRedirect(route('admin.assets.create'))
        ->assertSessionHasErrors('current_value');
});

it('validates current value cannot exceed purchase price', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.assets.create'))
        ->post(route('admin.assets.store'), [
            'name' => 'Invalid Asset',
            'asset_category_id' => $this->category->id,
            'purchased_at' => '2026-08-16',
            'purchase_price' => 10000,
            'current_value' => 15000,
        ])
        ->assertRedirect(route('admin.assets.create'))
        ->assertSessionHasErrors('current_value');
});

it('forbids asset management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.assets.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.assets.show', $asset))
        ->assertForbidden();
});

it('shows assets in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Assets', false);
});

it('generates unique asset codes for same-day records', function () {
    $first = app(AssetRepository::class)->nextAssetCode();
    Asset::factory()->create(['asset_code' => $first, 'asset_category_id' => $this->category->id]);
    $second = app(AssetRepository::class)->nextAssetCode();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('AST-'.now()->format('Ymd'));
});
