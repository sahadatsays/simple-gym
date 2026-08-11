<?php

use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Jobs\MemberAccessRevokeJob;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'adminuser',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    config(['queue.default' => 'sync']);
});

it('lists rfid cards with search and status filter', function () {
    $member = Member::factory()->create(['name' => 'Card Holder']);

    RfidCard::factory()->create([
        'card_number' => 'RFID10001',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    RfidCard::factory()->create([
        'card_number' => 'RFID99999',
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.rfid-cards.index', ['search' => 'Card Holder', 'status' => 'active']))
        ->assertSuccessful()
        ->assertSee('RFID10001')
        ->assertDontSee('RFID99999');
});

it('registers a new unassigned rfid card', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.store'), [
            'card_number' => 'RFIDNEW001',
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    $card = RfidCard::query()->where('card_number', 'RFIDNEW001')->first();

    expect($card)->not->toBeNull()
        ->and($card->status)->toBe(RfidCardStatus::Unassigned);
});

it('assigns a card and disables previous active cards for the member', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDOLD001']);

    $oldCard = RfidCard::factory()->create([
        'card_number' => 'RFIDOLD001',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now()->subMonth(),
    ]);

    $newCard = RfidCard::factory()->create([
        'card_number' => 'RFIDNEW002',
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $newCard), [
            'member_id' => $member->id,
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($newCard->fresh()->status)->toBe(RfidCardStatus::Active)
        ->and($newCard->fresh()->member_id)->toBe($member->id)
        ->and($oldCard->fresh()->status)->toBe(RfidCardStatus::Disabled)
        ->and($member->fresh()->rfid_card)->toBe('RFIDNEW002');
});

it('replaces a member card and disables the previous card', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDOLD003']);

    $oldCard = RfidCard::factory()->create([
        'card_number' => 'RFIDOLD003',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now()->subWeek(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.replace'), [
            'member_id' => $member->id,
            'card_number' => 'RFIDREPLACE004',
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    $newCard = RfidCard::query()->where('card_number', 'RFIDREPLACE004')->first();

    expect($newCard)->not->toBeNull()
        ->and($newCard->status)->toBe(RfidCardStatus::Active)
        ->and($oldCard->fresh()->status)->toBe(RfidCardStatus::Disabled)
        ->and($member->fresh()->rfid_card)->toBe('RFIDREPLACE004');
});

it('disables an active card and clears member rfid reference', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDDISABLE005']);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDDISABLE005',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.disable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($card->fresh()->status)->toBe(RfidCardStatus::Disabled)
        ->and($member->fresh()->rfid_card)->toBeNull();
});

it('queues device user delete when disabling an active card', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20010',
        'rfid_card' => 'RFIDDISABLE010',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDDISABLE010',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.disable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toBe('DATA DELETE user Pin='.$card->id);
});

it('dispatches member access revoke job when disabling a card', function () {
    Queue::fake();

    $member = Member::factory()->create([
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.disable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    Queue::assertPushed(MemberAccessRevokeJob::class, fn (MemberAccessRevokeJob $job): bool => $job->memberId === $member->id && $job->rfidCardId === $card->id);
});

it('enables a disabled card for a non-expired member and queues device sync', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20011',
        'name' => 'Enabled Member',
        'rfid_card' => null,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDENABLE011',
        'status' => RfidCardStatus::Disabled,
        'member_id' => $member->id,
        'assigned_at' => now()->subWeek(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.enable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($card->fresh()->status)->toBe(RfidCardStatus::Active)
        ->and($member->fresh()->rfid_card)->toBe('RFIDENABLE011')
        ->and(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))
        ->toContain('Pin='.$card->id)
        ->toContain('CardID=RFIDENABLE011');
});

it('prevents enabling a card for an expired member', function () {
    $member = Member::factory()->create([
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
        'rfid_card' => null,
    ]);

    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Disabled,
        'member_id' => $member->id,
        'assigned_at' => now()->subMonth(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.enable', $card))
        ->assertRedirect();

    expect($card->fresh()->status)->toBe(RfidCardStatus::Disabled)
        ->and($member->fresh()->rfid_card)->toBeNull();
});

it('removes an unassigned rfid card without device sync', function () {
    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDREMOVE001',
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.rfid-cards.destroy', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect(RfidCard::query()->whereKey($card->id)->exists())->toBeFalse()
        ->and(ZktecoCommand::query()->count())->toBe(0);
});

it('removes an active card and queues device user delete', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20020',
        'rfid_card' => 'RFIDREMOVE020',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDREMOVE020',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.rfid-cards.destroy', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect(RfidCard::query()->whereKey($card->id)->exists())->toBeFalse()
        ->and($member->fresh()->rfid_card)->toBeNull()
        ->and(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toBe('DATA DELETE user Pin='.$card->id);
});

it('dispatches member access revoke job when removing an active card', function () {
    Queue::fake();

    $member = Member::factory()->create([
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
        'rfid_card' => 'RFIDREMOVE021',
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDREMOVE021',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.rfid-cards.destroy', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    Queue::assertPushed(MemberAccessRevokeJob::class, fn (MemberAccessRevokeJob $job): bool => $job->memberId === $member->id && $job->rfidCardId === $card->id);
});

it('prevents assigning a card that is not unassigned', function () {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->active()->create([
        'card_number' => 'RFIDACTIVE006',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect();

    expect($card->fresh()->member_id)->not->toBe($member->id);
});

it('denies rfid management without permission', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->post(route('admin.rfid-cards.store'), [
            'card_number' => 'RFIDNOACCESS',
        ])
        ->assertForbidden();
});
