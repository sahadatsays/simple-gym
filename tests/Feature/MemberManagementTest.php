<?php

use App\Enums\MemberStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->plan = MembershipPlan::factory()->create([
        'name' => 'Monthly Plan',
        'duration_days' => 30,
    ]);
});

it('lists members with search and filters', function () {
    $member = Member::factory()->create([
        'name' => 'John Member',
        'phone' => '01700000001',
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
    ]);

    Member::factory()->create([
        'name' => 'Jane Other',
        'phone' => '01700000002',
        'status' => MemberStatus::Suspended,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.index', [
            'search' => 'John',
            'status' => 'active',
            'membership_plan_id' => $this->plan->id,
        ]))
        ->assertSuccessful()
        ->assertSee('John Member')
        ->assertDontSee('Jane Other');
});

it('creates a member with auto generated member id and photo', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.members.store'), [
            'name' => 'New Member',
            'phone' => '01711112222',
            'email' => 'new@example.com',
            'gender' => 'male',
            'date_of_birth' => '1995-05-10',
            'address' => '123 Gym Street',
            'emergency_contact_name' => 'Emergency Person',
            'emergency_contact_phone' => '01799998888',
            'membership_plan_id' => $this->plan->id,
            'joined_at' => now()->toDateString(),
            'status' => 'active',
            'photo' => UploadedFile::fake()->image('member.jpg'),
        ])
        ->assertRedirect();

    $member = Member::query()->where('phone', '01711112222')->first();

    expect($member)->not->toBeNull()
        ->and($member->member_code)->toStartWith('M')
        ->and($member->photo_path)->not->toBeNull()
        ->and($member->membership_expires_at)->not->toBeNull();

    Storage::disk('public')->assertExists($member->photo_path);
});

it('rejects duplicate phone', function () {
    Member::factory()->create([
        'phone' => '01700000099',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.members.store'), [
            'name' => 'Duplicate Member',
            'phone' => '01700000099',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['phone']);
});

it('shows member profile page', function () {
    $member = Member::factory()->create([
        'name' => 'Profile Member',
        'membership_plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.show', $member))
        ->assertSuccessful()
        ->assertSee('Profile Member')
        ->assertSee($member->member_code)
        ->assertSee('Monthly Plan');
});

it('updates a member profile without changing membership', function () {
    $member = Member::factory()->create([
        'name' => 'Old Name',
        'phone' => '01722223333',
        'status' => MemberStatus::Active,
        'membership_plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.members.update', $member), [
            'name' => 'Updated Name',
            'phone' => '01722223333',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('admin.members.show', $member));

    $member->refresh();

    expect($member->name)->toBe('Updated Name')
        ->and($member->email)->toBe('updated@example.com')
        ->and($member->status)->toBe(MemberStatus::Active)
        ->and($member->membership_plan_id)->toBe($this->plan->id);
});

it('rejects membership field updates on member edit', function () {
    $otherPlan = MembershipPlan::factory()->create(['name' => 'Yearly Plan']);

    $member = Member::factory()->create([
        'membership_plan_id' => $this->plan->id,
        'status' => MemberStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.members.update', $member), [
            'name' => $member->name,
            'phone' => $member->phone,
            'membership_plan_id' => $otherPlan->id,
            'status' => 'suspended',
            'joined_at' => now()->subYear()->toDateString(),
        ])
        ->assertSessionHasErrors(['membership_plan_id', 'status', 'joined_at']);

    expect($member->fresh()->membership_plan_id)->toBe($this->plan->id)
        ->and($member->fresh()->status)->toBe(MemberStatus::Active);
});

it('soft deletes a member without history and frees unique fields', function () {
    $member = Member::factory()->create([
        'phone' => '01733334444',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.members.destroy', $member))
        ->assertRedirect(route('admin.members.index'));

    expect(Member::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(Member::withTrashed()->whereKey($member->id)->exists())->toBeTrue();

    $this->actingAs($this->admin)
        ->post(route('admin.members.store'), [
            'name' => 'Reused Phone',
            'phone' => '01733334444',
            'joined_at' => now()->toDateString(),
            'status' => 'active',
        ])
        ->assertRedirect();
});

it('prevents deleting a member with payment history', function () {
    $member = Member::factory()->create([
        'phone' => '01744445555',
    ]);

    Payment::factory()->for($member)->membershipFee()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.members.destroy', $member))
        ->assertRedirect()
        ->assertSessionHas('flash.type', 'danger');

    expect(Member::query()->whereKey($member->id)->exists())->toBeTrue();
});

it('prevents deleting a member with invoice history', function () {
    $member = Member::factory()->create([
        'phone' => '01755556666',
    ]);

    Invoice::factory()->for($member)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.members.destroy', $member))
        ->assertRedirect()
        ->assertSessionHas('flash.type', 'danger');

    expect(Member::query()->whereKey($member->id)->exists())->toBeTrue();
});

it('denies access without permission', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.members.create'))
        ->assertForbidden();
});
