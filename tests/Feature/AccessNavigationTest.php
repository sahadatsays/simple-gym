<?php

use App\Models\LockerReservation;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\Role;
use App\Models\User;
use App\Support\MenuBuilder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('shows access control and facilities in the sidebar', function () {
    $this->actingAs($this->admin);

    $groups = MenuBuilder::authorizedGroups();

    expect($groups->firstWhere('key', 'membership')['items']->pluck('key')->all())
        ->toBe(['members', 'plans', 'registration', 'renew'])
        ->and($groups->firstWhere('key', 'access_control')['items']->pluck('key')->all())
        ->toBe(['rfid_cards', 'access_time_periods'])
        ->and($groups->firstWhere('key', 'facilities')['items']->pluck('key')->all())
        ->toBe(['lockers'])
        ->and($groups->firstWhere('key', 'finance')['items']->pluck('key')->all())
        ->not->toContain('rfid_cards', 'lockers', 'access_time_periods');

    $this->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Access Control', false)
        ->assertSee('RFID Cards', false)
        ->assertSee('Access Time Periods', false)
        ->assertSee('Facilities', false)
        ->assertSee(route('admin.settings.edit').'#access-time-periods', false)
        ->assertSee(route('admin.rfid-cards.index'), false)
        ->assertSee(route('admin.lockers.index'), false);
});

it('hides access control and facilities without permission', function () {
    $trainer = User::factory()->create(['is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer);

    $keys = MenuBuilder::authorizedGroups()->pluck('key');

    expect($keys)->not->toContain('access_control', 'facilities');
});

it('shows only the access items a role is allowed to open', function () {
    $staff = User::factory()->create(['is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff);

    $groups = MenuBuilder::authorizedGroups();

    expect($groups->firstWhere('key', 'access_control')['items']->pluck('key')->all())
        ->toBe(['rfid_cards'])
        ->and($groups->firstWhere('key', 'facilities')['items']->pluck('key')->all())
        ->toBe(['lockers']);
});

it('controls card actions with separate permissions', function () {
    $viewer = User::factory()->create(['is_active' => true]);
    $viewer->givePermissionTo('rfid-cards.view');
    $card = RfidCard::factory()->create();
    $member = Member::factory()->create();

    $this->actingAs($viewer)
        ->get(route('admin.rfid-cards.index'))
        ->assertSuccessful();

    $this->actingAs($viewer)
        ->post(route('admin.rfid-cards.store'), ['card_number' => 'RFID-NAV-1'])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('admin.rfid-cards.assign', $card), ['member_id' => $member->id])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('admin.rfid-cards.replace'), [
            'member_id' => $member->id,
            'card_number' => 'RFID-NAV-2',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('admin.rfid-cards.return', $card))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('admin.rfid-cards.disable', $card))
        ->assertForbidden();

    $creator = User::factory()->create(['is_active' => true]);
    $creator->givePermissionTo('rfid-cards.create');

    $this->actingAs($creator)
        ->post(route('admin.rfid-cards.store'), ['card_number' => 'RFID-NAV-1'])
        ->assertRedirect(route('admin.rfid-cards.index'));

    $assigner = User::factory()->create(['is_active' => true]);
    $assigner->givePermissionTo('rfid-cards.assign');

    $this->actingAs($assigner)
        ->post(route('admin.rfid-cards.assign', $card), ['member_id' => $member->id])
        ->assertRedirect(route('admin.rfid-cards.index'));

    $this->actingAs($assigner)
        ->post(route('admin.rfid-cards.issue'), [
            'member_id' => Member::factory()->create()->id,
            'card_number' => 'RFID-NAV-ISSUE',
        ])
        ->assertRedirect();
});

it('controls locker reservation renewal and cancellation separately from locker editing', function () {
    $viewer = User::factory()->create(['is_active' => true]);
    $viewer->givePermissionTo(['lockers.view', 'lockers.create', 'lockers.edit']);
    $reservation = LockerReservation::factory()->create();

    $this->actingAs($viewer)
        ->get(route('admin.lockers.index'))
        ->assertSuccessful()
        ->assertSee('Reservations', false);

    $this->actingAs($viewer)
        ->get(route('admin.locker-reservations.create'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('admin.locker-reservations.renew', $reservation))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('admin.locker-reservations.cancel', $reservation))
        ->assertForbidden();

    $operator = User::factory()->create(['is_active' => true]);
    $operator->givePermissionTo(['lockers.view', 'lockers.reserve', 'lockers.renew', 'lockers.cancel']);

    $this->actingAs($operator)
        ->get(route('admin.locker-reservations.create'))
        ->assertSuccessful();

    $this->actingAs($operator)
        ->post(route('admin.locker-reservations.renew', $reservation))
        ->assertRedirect();

    $activeReservation = LockerReservation::factory()->create();

    $this->actingAs($operator)
        ->patch(route('admin.locker-reservations.cancel', $activeReservation))
        ->assertRedirect(route('admin.locker-reservations.show', $activeReservation));
});

it('copies existing card and locker grants onto the new action permissions', function () {
    $role = Role::query()->create([
        'name' => 'front-desk',
        'guard_name' => 'web',
        'display_name' => 'Front Desk',
    ]);

    Permission::findOrCreate('rfid-cards.manage');
    $role->givePermissionTo(['rfid-cards.manage', 'lockers.create']);

    $migration = require database_path('migrations/2026_10_03_145327_add_rfid_and_locker_action_permissions.php');
    $migration->up();

    $role->refresh();

    expect($role->hasPermissionTo('rfid-cards.assign'))->toBeTrue()
        ->and($role->hasPermissionTo('rfid-cards.return'))->toBeTrue()
        ->and($role->hasPermissionTo('rfid-cards.replace'))->toBeTrue()
        ->and($role->hasPermissionTo('rfid-cards.create'))->toBeTrue()
        ->and($role->hasPermissionTo('lockers.reserve'))->toBeTrue()
        ->and($role->hasPermissionTo('lockers.renew'))->toBeTrue()
        ->and($role->hasPermissionTo('lockers.cancel'))->toBeFalse()
        ->and(Permission::query()->where('name', 'rfid-cards.manage')->exists())->toBeFalse();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});
