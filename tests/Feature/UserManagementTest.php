<?php

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('lists users with search and filters', function () {
    $target = User::factory()->create([
        'name' => 'Jane Staff',
        'username' => 'janestaff',
        'email' => 'jane@example.com',
    ]);
    $target->assignRole('staff');

    User::factory()->inactive()->create(['username' => 'inactiveuser']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['search' => 'Jane', 'role' => 'staff', 'status' => 'active']))
        ->assertSuccessful()
        ->assertSee('Jane Staff')
        ->assertSee('janestaff')
        ->assertDontSee('inactiveuser');
});

it('creates a user with unique email and username', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'phone' => '1234567890',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'role' => 'staff',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('username', 'newuser')->exists())->toBeTrue();
});

it('rejects duplicate email and username', function () {
    User::factory()->create([
        'username' => 'existing',
        'email' => 'existing@example.com',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'Duplicate User',
            'username' => 'existing',
            'email' => 'existing@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'role' => 'staff',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors(['username', 'email']);
});

it('activates and deactivates users', function () {
    $user = User::factory()->inactive()->create(['username' => 'toggleuser']);
    $user->assignRole('staff');

    $this->actingAs($this->admin)
        ->patch(route('admin.users.activate', $user))
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeTrue();

    $this->actingAs($this->admin)
        ->patch(route('admin.users.deactivate', $user))
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();
});

it('resets a user password and logs activity', function () {
    $user = User::factory()->create(['username' => 'resetme']);
    $user->assignRole('staff');

    $this->actingAs($this->admin)
        ->put(route('admin.users.reset-password', $user), [
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])
        ->assertRedirect();

    expect(ActivityLog::query()->where('action', 'user.password_reset')->exists())->toBeTrue();
});

it('soft deletes users and frees unique fields', function () {
    $user = User::factory()->create([
        'username' => 'deleteme',
        'email' => 'deleteme@example.com',
    ]);
    $user->assignRole('staff');

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect(User::withTrashed()->find($user->id)?->trashed())->toBeTrue()
        ->and(User::query()->where('username', 'deleteme')->exists())->toBeFalse();
});
