<?php

use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('lists roles for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('super-admin');
});

it('creates a role with dynamic permissions', function () {
    Permission::findOrCreate('members.view');

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), [
            'name' => 'receptionist',
            'permissions' => ['members.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'receptionist')->first();

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('members.view'))->toBeTrue();
});

it('creates a permission and assigns it to super admin', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.permissions.store'), [
            'name' => 'members.archive',
        ])
        ->assertRedirect(route('admin.permissions.index'));

    expect(Permission::query()->where('name', 'members.archive')->exists())->toBeTrue();

    $superAdmin = Role::query()->where('name', 'super-admin')->first();

    expect($superAdmin?->hasPermissionTo('members.archive'))->toBeTrue();
});

it('hides unauthorized sidebar menu items', function () {
    $staff = User::factory()->create(['is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Roles', false)
        ->assertDontSee('Permissions', false);
});

it('forbids role management without permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('trainer');

    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

it('prevents deleting protected roles', function () {
    $role = Role::query()->where('name', 'super-admin')->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertForbidden();

    expect(Role::query()->where('name', 'super-admin')->exists())->toBeTrue();
});
