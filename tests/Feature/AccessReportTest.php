<?php

use App\Enums\LockerReservationStatus;
use App\Enums\RfidCardStatus;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\RfidCardAssignment;
use App\Models\User;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create(['username' => 'adminuser', 'is_active' => true]);
    $this->admin->assignRole('super-admin');
});

it('shows rfid and locker reports on the reports hub', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertSee('RFID Card Report')
        ->assertSee('Locker Report');
});

it('filters rfid card assignments by date, status, and card number', function () {
    $member = Member::factory()->create(['name' => 'Report Card Member', 'member_code' => 'M-RFID']);
    $card = RfidCard::factory()->create([
        'card_number' => 'RFID-REPORT',
        'status' => RfidCardStatus::Assigned,
        'member_id' => $member->id,
    ]);

    RfidCardAssignment::query()->create([
        'member_id' => $member->id,
        'rfid_card_id' => $card->id,
        'issue_date' => now(),
        'status' => RfidCardStatus::Assigned,
        'card_fee' => 0,
        'deposit_amount' => 0,
    ]);

    $oldCard = RfidCard::factory()->create(['card_number' => 'RFID-OLD']);

    RfidCardAssignment::query()->create([
        'member_id' => $member->id,
        'rfid_card_id' => $oldCard->id,
        'issue_date' => now()->subMonths(3),
        'status' => RfidCardStatus::Returned,
        'return_date' => now()->subMonths(2),
        'card_fee' => 0,
        'deposit_amount' => 0,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'rfid-cards',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'status' => RfidCardStatus::Assigned->value,
            'search' => 'RFID-REPORT',
        ]))
        ->assertSuccessful()
        ->assertSee('RFID Card Report')
        ->assertSee('Card')
        ->assertSee('Member')
        ->assertSee('Issue Date')
        ->assertSee('Status')
        ->assertSee('RFID-REPORT')
        ->assertSee('Report Card Member')
        ->assertSee('Assignments')
        ->assertDontSee('RFID-OLD');
});

it('filters locker reservations that overlap the selected dates', function () {
    $member = Member::factory()->create(['name' => 'Report Locker Member']);
    $locker = Locker::factory()->reserved()->create(['locker_number' => 'L-REPORT']);

    LockerReservation::factory()->create([
        'locker_id' => $locker->id,
        'member_id' => $member->id,
        'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
        'status' => LockerReservationStatus::Active,
    ]);

    $laterLocker = Locker::factory()->reserved()->create(['locker_number' => 'L-LATER']);

    LockerReservation::factory()->create([
        'locker_id' => $laterLocker->id,
        'member_id' => $member->id,
        'start_date' => now()->addYear()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->endOfMonth()->toDateString(),
        'status' => LockerReservationStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', [
            'report' => 'lockers',
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
            'status' => LockerReservationStatus::Active->value,
            'search' => 'L-REPORT',
        ]))
        ->assertSuccessful()
        ->assertSee('Locker Report')
        ->assertSee('Locker')
        ->assertSee('Start Date')
        ->assertSee('End Date')
        ->assertSee('L-REPORT')
        ->assertSee('Report Locker Member')
        ->assertSee('Reservations')
        ->assertDontSee('L-LATER');
});

it('hides rfid and locker reports without module permissions', function () {
    $trainer = User::factory()->create(['username' => 'traineruser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $this->actingAs($trainer)
        ->get(route('admin.reports.index'))
        ->assertSuccessful()
        ->assertDontSee('RFID Card Report')
        ->assertDontSee('Locker Report');

    $this->actingAs($trainer)
        ->get(route('admin.reports.show', 'rfid-cards'))
        ->assertForbidden();

    $this->actingAs($trainer)
        ->get(route('admin.reports.show', 'lockers'))
        ->assertForbidden();
});
