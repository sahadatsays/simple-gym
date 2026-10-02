<?php

use App\Enums\LockerStatus;
use App\Models\Locker;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'name' => 'Locker Admin',
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('lists lockers with search and status filter', function () {
    Locker::factory()->create([
        'locker_number' => 'A-01',
        'location' => 'Ground floor',
        'status' => LockerStatus::Available,
    ]);

    Locker::factory()->create([
        'locker_number' => 'B-09',
        'location' => 'First floor',
        'status' => LockerStatus::Maintenance,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.lockers.index', ['search' => 'Ground', 'status' => 'available']))
        ->assertSuccessful()
        ->assertSee('A-01')
        ->assertDontSee('B-09')
        ->assertSee('table-responsive')
        ->assertSee('d-none d-md-table-cell');
});

it('paginates the locker list', function () {
    config(['gym.pagination.per_page' => 1]);

    Locker::factory()->create(['locker_number' => 'A-01']);
    Locker::factory()->create(['locker_number' => 'B-02']);

    $this->actingAs($this->admin)
        ->get(route('admin.lockers.index'))
        ->assertSuccessful()
        ->assertSee('A-01')
        ->assertDontSee('B-02');

    $this->actingAs($this->admin)
        ->get(route('admin.lockers.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertSee('B-02')
        ->assertDontSee('A-01');
});

it('creates a locker and records who created it', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.lockers.store'), [
            'locker_number' => 'C-14',
            'location' => 'Women\'s area',
            'category' => 'Large',
            'monthly_fee' => 300,
            'status' => 'available',
            'notes' => 'Near the entrance',
        ])
        ->assertRedirect();

    $locker = Locker::query()->where('locker_number', 'C-14')->first();

    expect($locker)->not->toBeNull()
        ->and($locker->location)->toBe('Women\'s area')
        ->and($locker->category)->toBe('Large')
        ->and((float) $locker->monthly_fee)->toBe(300.0)
        ->and($locker->status)->toBe(LockerStatus::Available)
        ->and($locker->notes)->toBe('Near the entrance')
        ->and($locker->created_by)->toBe($this->admin->id);

    $this->actingAs($this->admin)
        ->get(route('admin.lockers.show', $locker))
        ->assertSuccessful()
        ->assertSee('Locker Admin')
        ->assertSee('Large');
});

it('rejects a duplicate locker number and a negative monthly fee', function () {
    Locker::factory()->create(['locker_number' => 'A-01']);

    $this->actingAs($this->admin)
        ->from(route('admin.lockers.create'))
        ->post(route('admin.lockers.store'), [
            'locker_number' => 'A-01',
            'monthly_fee' => -10,
            'status' => 'available',
        ])
        ->assertRedirect(route('admin.lockers.create'))
        ->assertSessionHasErrors(['locker_number', 'monthly_fee']);

    expect(Locker::query()->count())->toBe(1);
});

it('reserves an available locker', function () {
    $locker = Locker::factory()->create([
        'locker_number' => 'A-02',
        'status' => LockerStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.lockers.update', $locker), [
            'locker_number' => 'A-02',
            'monthly_fee' => 0,
            'status' => 'reserved',
        ])
        ->assertRedirect(route('admin.lockers.show', $locker));

    expect($locker->fresh()->status)->toBe(LockerStatus::Reserved);
});

it('does not reserve a disabled or maintenance locker', function (LockerStatus $status) {
    $locker = Locker::factory()->create([
        'locker_number' => 'A-03',
        'status' => $status,
        'monthly_fee' => 100,
    ]);

    $this->actingAs($this->admin)
        ->from(route('admin.lockers.edit', $locker))
        ->put(route('admin.lockers.update', $locker), [
            'locker_number' => 'A-03',
            'monthly_fee' => 100,
            'status' => 'reserved',
        ])
        ->assertRedirect(route('admin.lockers.edit', $locker))
        ->assertSessionHasErrors('status');

    expect($locker->fresh()->status)->toBe($status);
})->with([
    'maintenance' => LockerStatus::Maintenance,
    'disabled' => LockerStatus::Disabled,
]);

it('updates and deletes a locker', function () {
    $locker = Locker::factory()->create([
        'locker_number' => 'A-04',
        'monthly_fee' => 50,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.lockers.update', $locker), [
            'locker_number' => 'A-04',
            'location' => 'First floor',
            'category' => 'Premium',
            'monthly_fee' => 450,
            'status' => 'available',
            'notes' => 'Updated note',
        ])
        ->assertRedirect(route('admin.lockers.show', $locker));

    expect($locker->fresh()->location)->toBe('First floor')
        ->and((float) $locker->fresh()->monthly_fee)->toBe(450.0);

    $this->actingAs($this->admin)
        ->delete(route('admin.lockers.destroy', $locker))
        ->assertRedirect(route('admin.lockers.index'));

    expect(Locker::query()->count())->toBe(0)
        ->and(Locker::withTrashed()->count())->toBe(1);
});

it('denies locker management without permission', function () {
    $staff = User::factory()->create([
        'username' => 'staffuser',
        'is_active' => true,
    ]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.lockers.index'))
        ->assertSuccessful();

    $this->actingAs($staff)
        ->post(route('admin.lockers.store'), [
            'locker_number' => 'Z-99',
            'monthly_fee' => 0,
            'status' => 'available',
        ])
        ->assertForbidden();
});
