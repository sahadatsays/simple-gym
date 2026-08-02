<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create([
        'email' => 'admin@simplegym.test',
        'password' => 'password',
        'is_active' => true,
    ]);

    $this->user->assignRole('super-admin');
});

it('displays the login page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Sign in to your admin account')
        ->assertSee('you@example.com', false);
});

it('authenticates active users and redirects to dashboard', function () {
    $this->post(route('login'), [
        'email' => 'admin@simplegym.test',
        'password' => 'password',
    ])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($this->user);
});

it('rejects invalid credentials', function () {
    $this->post(route('login'), [
        'email' => 'admin@simplegym.test',
        'password' => 'wrong-password',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('submits the login form with a submit button', function () {
    $response = $this->get(route('login'));

    $response->assertSee('type="submit"', false);
    $response->assertSee('class="btn sg-auth-btn w-100"', false);
});
