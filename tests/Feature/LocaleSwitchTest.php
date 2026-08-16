<?php

use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses english as the default locale', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('English', false);

    expect(app()->getLocale())->toBe('en');
});

it('stores bangla in the session and applies it on the next request', function () {
    $this->from(route('login'))
        ->get(route('locale.switch', 'bn'))
        ->assertRedirect(route('login'));

    expect(session('locale'))->toBe('bn');

    $this->get(route('login'))
        ->assertSuccessful();

    expect(app()->getLocale())->toBe('bn');
});

it('falls back to english for unsupported locales', function () {
    $this->from(route('login'))
        ->get(route('locale.switch', 'fr'))
        ->assertRedirect(route('login'));

    expect(session('locale'))->toBe('en');

    $this->get(route('login'))
        ->assertSuccessful();

    expect(app()->getLocale())->toBe('en');
});

it('redirects authenticated users back to the current page', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->from(route('admin.products.index'))
        ->get(route('locale.switch', 'bn'))
        ->assertRedirect(route('admin.products.index'));
});

it('shows the locale switcher on auth and admin layouts', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('English', false)
        ->assertSee('বাংলা', false);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('English', false)
        ->assertSee('বাংলা', false);
});
