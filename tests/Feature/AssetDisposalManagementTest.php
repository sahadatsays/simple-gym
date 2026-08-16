<?php

use App\Enums\AssetDisposalType;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
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

    $this->category = AssetCategory::factory()->create();
    $this->asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'status' => AssetStatus::Active,
        'purchase_price' => 50000,
    ]);
});

it('lists disposal records for authorized users', function () {
    AssetDisposal::factory()->sold()->create([
        'asset_id' => $this->asset->id,
        'buyer' => 'Second Hand Gym',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-disposals.index'))
        ->assertSuccessful()
        ->assertSee('Asset Disposals')
        ->assertSee($this->asset->name)
        ->assertSee('Second Hand Gym');
});

it('disposes an asset through the confirm workflow and updates status', function () {
    AssetMaintenance::factory()->create([
        'asset_id' => $this->asset->id,
        'cost' => 1500,
    ]);

    $payload = [
        'asset_id' => $this->asset->id,
        'disposed_at' => '2026-08-16',
        'disposal_type' => AssetDisposalType::Sold->value,
        'sale_amount' => 20000,
        'buyer' => 'Second Hand Gym',
        'reason' => 'Upgraded to newer model',
        'notes' => 'Sold with warranty transfer',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.confirm'), $payload)
        ->assertSuccessful()
        ->assertSee('Confirm Asset Disposal')
        ->assertSee('Second Hand Gym')
        ->assertSee('Sold');

    $response = $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.store'), $payload);

    $disposal = AssetDisposal::query()->first();

    expect($disposal)->not->toBeNull()
        ->and($disposal->asset_id)->toBe($this->asset->id)
        ->and($disposal->disposal_type)->toBe(AssetDisposalType::Sold)
        ->and((float) $disposal->sale_amount)->toBe(20000.0)
        ->and($disposal->created_by)->toBe($this->admin->id)
        ->and($this->asset->fresh()->status)->toBe(AssetStatus::Sold)
        ->and(AssetMaintenance::query()->where('asset_id', $this->asset->id)->count())->toBe(1)
        ->and($this->asset->fresh()->trashed())->toBeFalse();

    $response->assertRedirect(route('admin.assets.show', $this->asset));
});

it('maps disposal types to the correct asset statuses', function (AssetDisposalType $type, AssetStatus $expectedStatus) {
    $asset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'status' => AssetStatus::Active,
    ]);

    $payload = [
        'asset_id' => $asset->id,
        'disposed_at' => '2026-08-16',
        'disposal_type' => $type->value,
        'sale_amount' => $type === AssetDisposalType::Sold ? 5000 : null,
        'reason' => 'Test disposal',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.store'), $payload)
        ->assertRedirect(route('admin.assets.show', $asset));

    expect($asset->fresh()->status)->toBe($expectedStatus);
})->with([
    'sold' => [AssetDisposalType::Sold, AssetStatus::Sold],
    'disposed' => [AssetDisposalType::Disposed, AssetStatus::Disposed],
    'lost' => [AssetDisposalType::Lost, AssetStatus::Lost],
    'damaged beyond repair' => [AssetDisposalType::DamagedBeyondRepair, AssetStatus::Damaged],
]);

it('requires sale amount for sold disposals', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.asset-disposals.create'))
        ->post(route('admin.asset-disposals.confirm'), [
            'asset_id' => $this->asset->id,
            'disposed_at' => '2026-08-16',
            'disposal_type' => AssetDisposalType::Sold->value,
        ])
        ->assertRedirect(route('admin.asset-disposals.create'))
        ->assertSessionHasErrors('sale_amount');

    expect(AssetDisposal::query()->count())->toBe(0);
});

it('rejects negative sale amounts', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.confirm'), [
            'asset_id' => $this->asset->id,
            'disposed_at' => '2026-08-16',
            'disposal_type' => AssetDisposalType::Sold->value,
            'sale_amount' => -100,
        ])
        ->assertSessionHasErrors('sale_amount');
});

it('rejects disposal for already sold assets', function () {
    $this->asset->update(['status' => AssetStatus::Sold]);

    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.store'), [
            'asset_id' => $this->asset->id,
            'disposed_at' => '2026-08-16',
            'disposal_type' => AssetDisposalType::Disposed->value,
            'reason' => 'Attempted duplicate disposal',
        ])
        ->assertSessionHasErrors('asset_id');

    expect(AssetDisposal::query()->count())->toBe(0);
});

it('rejects disposal for already disposed assets', function () {
    $this->asset->update(['status' => AssetStatus::Disposed]);

    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.store'), [
            'asset_id' => $this->asset->id,
            'disposed_at' => '2026-08-16',
            'disposal_type' => AssetDisposalType::Lost->value,
            'reason' => 'Attempted duplicate disposal',
        ])
        ->assertSessionHasErrors('asset_id');
});

it('rejects disposal when a disposal record already exists', function () {
    AssetDisposal::factory()->create([
        'asset_id' => $this->asset->id,
        'disposal_type' => AssetDisposalType::Lost,
    ]);

    $this->asset->update(['status' => AssetStatus::Lost]);

    $otherAsset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'status' => AssetStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.asset-disposals.store'), [
            'asset_id' => $this->asset->id,
            'disposed_at' => '2026-08-17',
            'disposal_type' => AssetDisposalType::Disposed->value,
            'reason' => 'Duplicate attempt',
        ])
        ->assertSessionHasErrors('asset_id');

    expect(AssetDisposal::query()->count())->toBe(1);
});

it('pre-selects asset when disposing from asset details', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.asset-disposals.create', ['asset_id' => $this->asset->id]))
        ->assertSuccessful()
        ->assertSee($this->asset->name);
});

it('shows dispose asset action on eligible asset details', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.assets.show', $this->asset))
        ->assertSuccessful()
        ->assertSee('Dispose Asset');
});

it('hides dispose asset action after disposal', function () {
    AssetDisposal::factory()->sold()->create([
        'asset_id' => $this->asset->id,
    ]);

    $this->asset->update(['status' => AssetStatus::Sold]);

    $this->actingAs($this->admin)
        ->get(route('admin.assets.show', $this->asset))
        ->assertSuccessful()
        ->assertDontSee('Dispose Asset')
        ->assertSee('View disposal details');
});

it('forbids disposal management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.asset-disposals.index'))
        ->assertForbidden();
});

it('filters disposal records by asset and type', function () {
    $otherAsset = Asset::factory()->create([
        'asset_category_id' => $this->category->id,
        'name' => 'Hidden Asset',
    ]);

    AssetDisposal::factory()->create([
        'asset_id' => $this->asset->id,
        'disposal_type' => AssetDisposalType::Sold,
        'buyer' => 'Visible Buyer Co',
    ]);

    AssetDisposal::factory()->create([
        'asset_id' => $otherAsset->id,
        'disposal_type' => AssetDisposalType::Lost,
        'buyer' => 'Hidden Buyer Co',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.asset-disposals.index', [
            'asset_id' => $this->asset->id,
            'disposal_type' => AssetDisposalType::Sold->value,
        ]))
        ->assertSuccessful()
        ->assertSee('Visible Buyer Co')
        ->assertDontSee('Hidden Buyer Co');
});
