<?php

use App\Enums\LockerReservationStatus;
use App\Enums\LockerStatus;
use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Models\Invoice;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\RfidCardAssignment;
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
        ->assertSee('Monthly Plan')
        ->assertSee('Transaction Summary')
        ->assertSee('Total Paid')
        ->assertSee('Total Due');
});

it('shows profile validation errors beside the fields and at the top of the form', function () {
    $member = Member::factory()->create([
        'name' => 'Editable Member',
        'phone' => '01722220001',
        'membership_plan_id' => $this->plan->id,
    ]);

    Member::factory()->create(['phone' => '01722220002']);

    $this->actingAs($this->admin)
        ->from(route('admin.members.edit', $member))
        ->followingRedirects()
        ->put(route('admin.members.update', $member), [
            'name' => '',
            'phone' => '01722220002',
            'email' => 'not-an-email',
        ])
        ->assertSuccessful()
        ->assertSee('Please fix the following:')
        ->assertSee('The name field is required.')
        ->assertSee('The phone has already been taken.')
        ->assertSee('The email field must be a valid email address.')
        ->assertSee('name="phone"', false)
        ->assertSee('is-invalid', false);

    expect($member->fresh()->name)->toBe('Editable Member');
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

it('shows rfid card and locker details on the member profile', function () {
    $member = Member::factory()->create([
        'name' => 'Access Member',
        'membership_plan_id' => $this->plan->id,
    ]);
    $currentCard = RfidCard::factory()->create([
        'card_number' => 'NEW-9',
        'status' => RfidCardStatus::Assigned,
        'member_id' => $member->id,
        'assigned_at' => '2026-09-02 10:00:00',
    ]);
    $previousCard = RfidCard::factory()->create([
        'card_number' => 'OLD-1',
        'status' => RfidCardStatus::Returned,
    ]);
    RfidCardAssignment::query()->create([
        'member_id' => $member->id,
        'rfid_card_id' => $previousCard->id,
        'issue_date' => '2026-08-01 10:00:00',
        'return_date' => '2026-08-20 10:00:00',
        'card_fee' => 0,
        'deposit_amount' => 0,
        'status' => RfidCardStatus::Returned,
    ]);
    RfidCardAssignment::query()->create([
        'member_id' => $member->id,
        'rfid_card_id' => $currentCard->id,
        'issue_date' => '2026-09-02 10:00:00',
        'return_date' => null,
        'card_fee' => 0,
        'deposit_amount' => 0,
        'status' => RfidCardStatus::Assigned,
    ]);
    $locker = Locker::factory()->create([
        'locker_number' => 'L-77',
        'monthly_fee' => 250,
        'status' => LockerStatus::Reserved,
    ]);
    $reservation = LockerReservation::factory()->create([
        'locker_id' => $locker->id,
        'member_id' => $member->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        'monthly_fee' => 250,
        'status' => LockerReservationStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.show', $member))
        ->assertSuccessful()
        ->assertSee('RFID Card')
        ->assertSee('NEW-9')
        ->assertSee('OLD-1')
        ->assertSee('Sep 2, 2026')
        ->assertSee('Replace')
        ->assertSee('Return card')
        ->assertDontSee('Issue card')
        ->assertSee('Locker')
        ->assertSee('L-77')
        ->assertSee('Oct 1, 2026')
        ->assertSee('Oct 31, 2026')
        ->assertSee(route('admin.locker-reservations.renew', $reservation), false);

    $staff = User::factory()->create([
        'username' => 'profilestaff',
        'is_active' => true,
    ]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.members.show', $member))
        ->assertSuccessful()
        ->assertSee('NEW-9')
        ->assertSee('L-77')
        ->assertDontSee('Issue card')
        ->assertDontSee('Replace')
        ->assertDontSee('Return card')
        ->assertDontSee(route('admin.locker-reservations.renew', $reservation), false);
});

it('issues an rfid card from the member profile', function () {
    $member = Member::factory()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.members.show', $member))
        ->post(route('admin.rfid-cards.issue'), [
            'member_id' => $member->id,
            'card_number' => 'SCAN-1',
        ])
        ->assertRedirect(route('admin.members.show', $member));

    expect($member->fresh()->activeRfidCard?->card_number)->toBe('SCAN-1')
        ->and($member->rfidCardAssignments()->count())->toBe(1);
});

it('denies access without permission', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.members.create'))
        ->assertForbidden();
});
