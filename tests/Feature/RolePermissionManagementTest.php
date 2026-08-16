<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('shows roles page in bangla locale', function () {
    $this->actingAs($this->admin)
        ->withSession(['locale' => 'bn'])
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('ভূমিকা', false)
        ->assertSee('সুপার অ্যাডমিন', false)
        ->assertSee('super-admin');
});

it('shows permissions page in bangla locale', function () {
    $this->actingAs($this->admin)
        ->withSession(['locale' => 'bn'])
        ->get(route('admin.permissions.index'))
        ->assertSuccessful()
        ->assertSee('অনুমতি', false)
        ->assertSee('ড্যাশবোর্ড দেখুন', false);
});

it('lists roles for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('Super Admin')
        ->assertSee('super-admin');
});

it('shows role create form with name and slug fields', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.create'))
        ->assertSuccessful()
        ->assertSee('Role name', false)
        ->assertSee('Slug', false)
        ->assertSee('name="display_name"', false)
        ->assertSee('name="slug"', false);
});

it('creates a role with display name, slug, and permissions', function () {
    Permission::findOrCreate('members.view');

    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), [
            'display_name' => 'Receptionist',
            'slug' => 'receptionist',
            'permissions' => ['members.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'receptionist')->first();

    expect($role)->not->toBeNull()
        ->and($role->display_name)->toBe('Receptionist')
        ->and($role->hasPermissionTo('members.view'))->toBeTrue();
});

it('updates a role display name and slug', function () {
    $role = Role::query()->create([
        'name' => 'front-desk',
        'display_name' => 'Front Desk',
        'guard_name' => 'web',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.roles.update', $role), [
            'display_name' => 'Front Desk Team',
            'slug' => 'front-desk-team',
            'permissions' => [],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role->refresh();

    expect($role->display_name)->toBe('Front Desk Team')
        ->and($role->name)->toBe('front-desk-team');
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

it('shows permissions in the sidebar for authorized users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Permissions', false);
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

it('prevents changing protected role slug and permissions', function () {
    $role = Role::query()->where('name', 'super-admin')->firstOrFail();
    $originalPermissionCount = $role->permissions()->count();

    $this->actingAs($this->admin)
        ->put(route('admin.roles.update', $role), [
            'display_name' => 'Super Admin',
            'slug' => 'changed-slug',
            'permissions' => [],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role->refresh();

    expect($role->name)->toBe('super-admin')
        ->and($role->permissions()->count())->toBe($originalPermissionCount);
});

it('lists all configured permissions on the role form', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.roles.create'));

    foreach (config('permissions.groups') as $permissions) {
        foreach ($permissions as $permission) {
            $response->assertSee($permission, false);
        }
    }
});

it('prevents updating default permissions', function () {
    $permission = Permission::query()->where('name', 'members.view')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('admin.permissions.edit', $permission))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->put(route('admin.permissions.update', $permission), [
            'name' => 'members.read',
        ])
        ->assertForbidden();

    expect(Permission::query()->where('name', 'members.view')->exists())->toBeTrue();
});

it('prevents deleting default permissions', function () {
    $permission = Permission::query()->where('name', 'dashboard.view')->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.permissions.destroy', $permission))
        ->assertForbidden();

    expect(Permission::query()->where('name', 'dashboard.view')->exists())->toBeTrue();
});

it('prevents creating permissions that match system defaults', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.permissions.store'), [
            'name' => 'payments.view',
        ])
        ->assertSessionHasErrors('name');
});

it('updates and deletes custom permissions', function () {
    $permission = Permission::findOrCreate('members.archive');

    $this->actingAs($this->admin)
        ->put(route('admin.permissions.update', $permission), [
            'name' => 'members.restore',
        ])
        ->assertRedirect(route('admin.permissions.index'));

    expect(Permission::query()->where('name', 'members.restore')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'members.archive')->exists())->toBeFalse();

    $permission = Permission::query()->where('name', 'members.restore')->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.permissions.destroy', $permission))
        ->assertRedirect(route('admin.permissions.index'));

    expect(Permission::query()->where('name', 'members.restore')->exists())->toBeFalse();
});

it('uses only configured default permissions across authorization checks', function () {
    $configuredPermissions = collect(config('permissions.groups'))->flatten()->unique()->sort()->values();

    $usedPermissions = collect([
        'dashboard.view',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'members.view', 'members.create', 'members.edit', 'members.delete',
        'membership-plans.view', 'membership-plans.create', 'membership-plans.edit', 'membership-plans.delete',
        'rfid-cards.view', 'rfid-cards.manage',
        'payments.view', 'payments.create',
        'products.view', 'products.manage',
        'reports.view',
        'settings.view', 'settings.update',
        'zkteco-devices.view', 'zkteco-devices.manage',
        'attendance-logs.view',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',
    ])->sort()->values();

    expect($usedPermissions->diff($configuredPermissions)->isEmpty())->toBeTrue();
});

it('syncs all default permissions through the seeder', function () {
    foreach (config('permissions.groups') as $permissions) {
        foreach ($permissions as $permission) {
            expect(Permission::query()->where('name', $permission)->exists())->toBeTrue();
        }
    }
});
