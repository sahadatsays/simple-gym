<?php

use App\Models\User;
use Database\Seeders\DashboardSeeder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->user->assignRole('super-admin');
    $this->seed(DashboardSeeder::class);
});

it('displays dashboard widgets for authorized users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Total Members')
        ->assertSee('Active Members')
        ->assertSee('Expired Members')
        ->assertSee("Today's Collection")
        ->assertSee('Monthly Collection')
        ->assertSee('Product Sales')
        ->assertSee('Low Stock Products')
        ->assertSee('Recent Members')
        ->assertSee('Recent Payments')
        ->assertSee('Monthly Revenue')
        ->assertSee('Membership Growth');
});

it('forbids dashboard access without permission', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
