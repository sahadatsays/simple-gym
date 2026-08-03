<?php

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Product;
use App\Models\User;
use App\Notifications\GymAlertNotification;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->user = User::factory()->create(['is_active' => true]);
    $this->user->assignRole('super-admin');

    $this->plan = MembershipPlan::factory()->create();
});

it('syncs membership alert notifications when visiting the dashboard', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDays(3),
        'name' => 'Expired Alert Member',
    ]);

    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addDays(3),
        'name' => 'Expiring Alert Member',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Alerts')
        ->assertSee('Expired Membership')
        ->assertSee('Membership Expiring in 7 Days');
});

it('shows low stock on the dashboard and birthday alerts in notifications', function () {
    Product::factory()->lowStock()->create([
        'name' => 'Low Stock Alert Product',
        'stock' => 2,
        'minimum_stock' => 5,
    ]);

    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'name' => 'Birthday Alert Member',
        'date_of_birth' => now()->subYears(25),
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Low Stock Products')
        ->assertSee('Low Stock Alert Product')
        ->assertDontSee('Alerts')
        ->assertSee("Today's Birthdays")
        ->assertDontSee('Birthday Alert Member');
});

it('syncs laravel notifications for dashboard alerts', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertSuccessful();

    expect($this->user->unreadNotifications()->count())->toBeGreaterThan(0)
        ->and($this->user->unreadNotifications()->first()->type)->toBe(GymAlertNotification::class);
});

it('shows unread notification counter in the topbar', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->user)->get(route('admin.dashboard'));

    $this->actingAs($this->user)
        ->get(route('admin.members.index'))
        ->assertSuccessful()
        ->assertSee('badge rounded-pill text-bg-danger', false);
});

it('lists notifications and marks them as read', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->user)->get(route('admin.dashboard'));

    $notification = $this->user->unreadNotifications()->first();

    expect($notification)->not->toBeNull();

    $this->actingAs($this->user)
        ->get(route('admin.notifications.index'))
        ->assertSuccessful()
        ->assertSee('Notifications')
        ->assertSee($notification->data['title']);

    $this->actingAs($this->user)
        ->post(route('admin.notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    Product::factory()->lowStock()->create(['stock' => 1, 'minimum_stock' => 5]);

    $this->actingAs($this->user)->get(route('admin.dashboard'));

    expect($this->user->unreadNotifications()->count())->toBeGreaterThan(0);

    $this->actingAs($this->user)
        ->post(route('admin.notifications.read-all'))
        ->assertRedirect(route('admin.notifications.index'));

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('syncs gym alerts via artisan command', function () {
    Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    Artisan::call('notifications:sync-gym-alerts');

    expect($this->user->fresh()->unreadNotifications()->count())->toBeGreaterThan(0);
});

it('denies notifications without dashboard permission', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.notifications.index'))
        ->assertForbidden();
});
