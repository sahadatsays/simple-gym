<?php

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');

    $this->category = AssetCategory::factory()->create();
    $this->asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'status' => AssetStatus::Active,
        'purchase_price' => 50000,
    ]);
});

it('lists maintenance records for authorized users', function () {
    AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'type' => AssetMaintenanceType::Preventive,
        'service_provider' => 'FitPro Ltd',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-maintenances.index'))
        ->assertSuccessful()
        ->assertSee('Asset Maintenance')
        ->assertSee($this->asset->name)
        ->assertSee('FitPro Ltd');
});

it('creates a maintenance record and redirects to asset history', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.asset-maintenances.store'), [
            'asset_id' => $this->asset->id,
            'maintained_at' => '2026-08-16',
            'type' => AssetMaintenanceType::Preventive->value,
            'cost' => 2500,
            'service_provider' => 'FitPro Ltd',
            'description' => 'Belt lubrication and calibration',
            'next_maintenance_at' => '2027-02-16',
            'attachment' => UploadedFile::fake()->create('service-report.pdf', 100, 'application/pdf'),
        ]);

    $maintenance = AssetMaintenance::query()->first();

    expect($maintenance)->not->toBeNull()
        ->and($maintenance->asset_id)->toBe($this->asset->id)
        ->and((float) $maintenance->cost)->toBe(2500.0)
        ->and($maintenance->created_by)->toBe($this->admin->id)
        ->and($maintenance->attachment_path)->not->toBeNull()
        ->and($this->asset->fresh()->status)->toBe(AssetStatus::Active);

    Storage::disk('public')->assertExists($maintenance->attachment_path);

    $response->assertRedirect(route('admin.assets.show', $this->asset));
});

it('shows maintenance on the asset details page total', function () {
    AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'cost' => 1500,
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'cost' => 2500,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.assets.show', $this->asset))
        ->assertSuccessful()
        ->assertSee('4,000');
});

it('updates asset status only when explicitly selected', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.asset-maintenances.store'), [
            'asset_id' => $this->asset->id,
            'maintained_at' => '2026-08-16',
            'type' => AssetMaintenanceType::Corrective->value,
            'cost' => 1000,
            'asset_status' => AssetStatus::UnderMaintenance->value,
        ])
        ->assertRedirect(route('admin.assets.show', $this->asset));

    expect($this->asset->fresh()->status)->toBe(AssetStatus::UnderMaintenance);
});

it('rejects maintenance for disposed assets', function () {
    $this->asset->update(['status' => AssetStatus::Disposed]);

    $this->actingAs($this->admin)
        ->from(route('admin.asset-maintenances.create'))
        ->post(route('admin.asset-maintenances.store'), [
            'asset_id' => $this->asset->id,
            'maintained_at' => '2026-08-16',
            'type' => AssetMaintenanceType::Preventive->value,
        ])
        ->assertRedirect(route('admin.asset-maintenances.create'))
        ->assertSessionHasErrors('asset_id');

    expect(AssetMaintenance::query()->count())->toBe(0);
});

it('rejects maintenance for sold assets', function () {
    $this->asset->update(['status' => AssetStatus::Sold]);

    $this->actingAs($this->admin)
        ->post(route('admin.asset-maintenances.store'), [
            'asset_id' => $this->asset->id,
            'maintained_at' => '2026-08-16',
            'type' => AssetMaintenanceType::Preventive->value,
        ])
        ->assertSessionHasErrors('asset_id');
});

it('validates maintenance cost cannot be negative', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.asset-maintenances.create'))
        ->post(route('admin.asset-maintenances.store'), [
            'asset_id' => $this->asset->id,
            'maintained_at' => '2026-08-16',
            'type' => AssetMaintenanceType::Preventive->value,
            'cost' => -100,
        ])
        ->assertRedirect(route('admin.asset-maintenances.create'))
        ->assertSessionHasErrors('cost');
});

it('updates and deletes maintenance records', function () {
    $maintenance = AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'cost' => 1000,
        'attachment_path' => 'assets/maintenances/old.pdf',
    ]);

    Storage::disk('public')->put('assets/maintenances/old.pdf', 'old');

    $this->actingAs($this->admin)
        ->put(route('admin.asset-maintenances.update', $maintenance), [
            'maintained_at' => '2026-08-17',
            'type' => AssetMaintenanceType::Inspection->value,
            'cost' => 2000,
            'description' => 'Updated inspection record',
        ])
        ->assertRedirect(route('admin.asset-maintenances.show', $maintenance));

    expect($maintenance->fresh()->description)->toBe('Updated inspection record')
        ->and((float) $maintenance->fresh()->cost)->toBe(2000.0);

    $this->actingAs($this->admin)
        ->delete(route('admin.asset-maintenances.destroy', $maintenance))
        ->assertRedirect(route('admin.assets.show', $this->asset));

    expect(AssetMaintenance::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('assets/maintenances/old.pdf');
});

it('pre-selects asset when creating from asset details', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.asset-maintenances.create', ['asset_id' => $this->asset->id]))
        ->assertSuccessful()
        ->assertSee($this->asset->name);
});

it('forbids maintenance management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.asset-maintenances.index'))
        ->assertForbidden();
});

it('filters maintenance records by asset and type', function () {
    $this->asset->update(['name' => 'Visible Asset']);

    $otherAsset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'name' => 'Hidden Asset',
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'type' => AssetMaintenanceType::Preventive,
        'service_provider' => 'Visible Provider Co',
    ]);

    AssetMaintenance::factory()->create([
        'asset_id' => $otherAsset->id,
        'type' => AssetMaintenanceType::Corrective,
        'service_provider' => 'Hidden Provider Co',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-maintenances.index', [
            'asset_id' => $this->asset->id,
            'type' => AssetMaintenanceType::Preventive->value,
        ]))
        ->assertSuccessful()
        ->assertSee('Visible Provider Co')
        ->assertDontSee('Hidden Provider Co');
});
