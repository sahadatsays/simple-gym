<?php

use App\Enums\PlanStatus;
use App\Models\GymSetting;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('lists membership plans with search and status filter', function () {
    GymSetting::query()->first()?->update(['currency' => 'BDT']);

    MembershipPlan::factory()->create([
        'name' => 'Monthly Gold',
        'admission_fee' => 500,
        'membership_fee' => 1500,
        'status' => PlanStatus::Active,
    ]);

    MembershipPlan::factory()->inactive()->create([
        'name' => 'Legacy Plan',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.membership-plans.index', ['search' => 'Monthly', 'status' => 'active']))
        ->assertSuccessful()
        ->assertSee('Monthly Gold')
        ->assertSee('৳')
        ->assertDontSee('Legacy Plan');
});

it('creates a membership plan with features', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.membership-plans.store'), [
            'name' => 'Starter Plan',
            'duration_days' => 30,
            'admission_fee' => 100,
            'membership_fee' => 900,
            'description' => 'Entry level plan',
            'status' => 'active',
            'features_text' => "Gym access\nLocker room",
        ])
        ->assertRedirect(route('admin.membership-plans.index'));

    $plan = MembershipPlan::query()->where('name', 'Starter Plan')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->duration_days)->toBe(30)
        ->and($plan->features)->toBe(['Gym access', 'Locker room']);
});

it('validates required membership plan fields', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.membership-plans.store'), [])
        ->assertSessionHasErrors(['name', 'duration_days', 'admission_fee', 'membership_fee', 'status']);
});

it('updates a membership plan', function () {
    $plan = MembershipPlan::factory()->create([
        'name' => 'Old Plan',
        'duration_days' => 30,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.membership-plans.update', $plan), [
            'name' => 'Updated Plan',
            'duration_days' => 90,
            'admission_fee' => 200,
            'membership_fee' => 2500,
            'description' => 'Updated description',
            'status' => 'inactive',
            'features_text' => 'Sauna access',
        ])
        ->assertRedirect(route('admin.membership-plans.index'));

    $plan->refresh();

    expect($plan->name)->toBe('Updated Plan')
        ->and($plan->duration_days)->toBe(90)
        ->and($plan->status)->toBe(PlanStatus::Inactive)
        ->and($plan->features)->toBe(['Sauna access']);
});

it('prevents deleting a plan assigned to members', function () {
    $plan = MembershipPlan::factory()->create(['name' => 'Assigned Plan']);

    Member::factory()->create([
        'membership_plan_id' => $plan->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.membership-plans.destroy', $plan))
        ->assertRedirect();

    expect(MembershipPlan::query()->whereKey($plan->id)->exists())->toBeTrue();
});

it('deletes an unassigned membership plan', function () {
    $plan = MembershipPlan::factory()->create(['name' => 'Unused Plan']);

    $this->actingAs($this->admin)
        ->delete(route('admin.membership-plans.destroy', $plan))
        ->assertRedirect(route('admin.membership-plans.index'));

    expect(MembershipPlan::withTrashed()->whereKey($plan->id)->exists())->toBeTrue()
        ->and(MembershipPlan::query()->whereKey($plan->id)->exists())->toBeFalse();
});

it('denies access without permission', function () {
    $user = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $user->assignRole('staff');

    $this->actingAs($user)
        ->get(route('admin.membership-plans.index'))
        ->assertForbidden();
});
