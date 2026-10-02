<?php

use App\Enums\InvoiceType;
use App\Enums\MemberStatus;
use App\Enums\PaymentType;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Jobs\MemberAccessRevokeJob;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\RfidCard;
use App\Models\RfidCardAssignment;
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
        'status' => RfidCardStatus::Assigned,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    RfidCard::factory()->create([
        'card_number' => 'RFID99999',
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.rfid-cards.index', ['search' => 'Card Holder', 'status' => 'assigned']))
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
        ->and($card->status)->toBe(RfidCardStatus::Available);
});

it('assigns a card and disables previous active cards for the member', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDOLD001']);

    $oldCard = RfidCard::factory()->create([
        'card_number' => 'RFIDOLD001',
        'status' => RfidCardStatus::Assigned,
        'member_id' => $member->id,
        'assigned_at' => now()->subMonth(),
    ]);

    $newCard = RfidCard::factory()->create([
        'card_number' => 'RFIDNEW002',
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $newCard), [
            'member_id' => $member->id,
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($newCard->fresh()->status)->toBe(RfidCardStatus::Assigned)
        ->and($newCard->fresh()->member_id)->toBe($member->id)
        ->and($oldCard->fresh()->status)->toBe(RfidCardStatus::Returned)
        ->and($member->fresh()->rfid_card)->toBe('RFIDNEW002');
});

it('replaces a member card and disables the previous card', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDOLD003']);

    $oldCard = RfidCard::factory()->create([
        'card_number' => 'RFIDOLD003',
        'status' => RfidCardStatus::Assigned,
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
        ->and($newCard->status)->toBe(RfidCardStatus::Assigned)
        ->and($oldCard->fresh()->status)->toBe(RfidCardStatus::Returned)
        ->and($member->fresh()->rfid_card)->toBe('RFIDREPLACE004');
});

it('disables an active card and clears member rfid reference', function () {
    $member = Member::factory()->create(['rfid_card' => 'RFIDDISABLE005']);

    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDDISABLE005',
        'status' => RfidCardStatus::Assigned,
        'member_id' => $member->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.disable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($card->fresh()->status)->toBe(RfidCardStatus::Blocked)
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
        'status' => RfidCardStatus::Assigned,
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
        'status' => RfidCardStatus::Assigned,
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
        'status' => RfidCardStatus::Blocked,
        'member_id' => $member->id,
        'assigned_at' => now()->subWeek(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.enable', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect($card->fresh()->status)->toBe(RfidCardStatus::Assigned)
        ->and($member->fresh()->rfid_card)->toBe('RFIDENABLE011')
        ->and(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))
        ->toContain('Pin='.$card->id)
        ->toContain('CardNo=RFIDENABLE011');
});

it('prevents enabling a card for an expired member', function () {
    $member = Member::factory()->create([
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
        'rfid_card' => null,
    ]);

    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Blocked,
        'member_id' => $member->id,
        'assigned_at' => now()->subMonth(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.enable', $card))
        ->assertRedirect();

    expect($card->fresh()->status)->toBe(RfidCardStatus::Blocked)
        ->and($member->fresh()->rfid_card)->toBeNull();
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

it('rejects a duplicate card number', function () {
    RfidCard::factory()->create(['card_number' => 'RFIDDUP001']);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.store'), [
            'card_number' => 'RFIDDUP001',
            'card_fee' => 25,
            'deposit_amount' => 10,
        ])
        ->assertSessionHasErrors('card_number');

    expect(RfidCard::query()->where('card_number', 'RFIDDUP001')->count())->toBe(1);
});

it('records one assignment and collects the card fee through an invoice', function () {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDFEE001',
        'card_fee' => 100,
        'deposit_amount' => 50,
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    $assignment = RfidCardAssignment::query()->where('rfid_card_id', $card->id)->first();
    $invoice = Invoice::query()->where('member_id', $member->id)->where('type', InvoiceType::RfidCard)->first();
    $payment = Payment::query()->where('invoice_id', $invoice?->id)->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment->status)->toBe(RfidCardStatus::Assigned)
        ->and($assignment->return_date)->toBeNull()
        ->and((float) $assignment->card_fee)->toBe(100.0)
        ->and((float) $assignment->deposit_amount)->toBe(50.0)
        ->and($invoice)->not->toBeNull()
        ->and((float) $invoice->total)->toBe(150.0)
        ->and($payment)->not->toBeNull()
        ->and($payment->type)->toBe(PaymentType::RfidCard)
        ->and((float) $payment->amount)->toBe(150.0);

    $this->actingAs($this->admin)
        ->get(route('admin.rfid-cards.show', $card))
        ->assertSuccessful()
        ->assertSee('Assignment History')
        ->assertSee($member->name)
        ->assertSee($invoice->invoice_number);
});

it('requires a payment method when the card has a fee', function () {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->create([
        'card_fee' => 80,
        'deposit_amount' => 0,
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertSessionHasErrors('payment_method');

    expect($card->fresh()->status)->toBe(RfidCardStatus::Available)
        ->and(Invoice::query()->count())->toBe(0);
});

it('closes the previous assignment when a card is replaced and keeps both history rows', function () {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->create([
        'card_number' => 'RFIDHIST001',
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.replace'), [
            'member_id' => $member->id,
            'card_number' => 'RFIDHIST002',
        ])
        ->assertRedirect();

    $firstAssignment = RfidCardAssignment::query()->where('rfid_card_id', $card->id)->first();

    expect(RfidCard::query()->where('member_id', $member->id)->where('status', RfidCardStatus::Assigned)->count())->toBe(1)
        ->and($firstAssignment->status)->toBe(RfidCardStatus::Returned)
        ->and($firstAssignment->return_date)->not->toBeNull()
        ->and(RfidCardAssignment::query()->count())->toBe(2)
        ->and(RfidCardAssignment::query()->whereNull('return_date')->count())->toBe(1);
});

it('closes an assignment when the card is returned and does not delete the history', function () {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Available,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.rfid-cards.return', $card))
        ->assertRedirect(route('admin.rfid-cards.index'));

    $assignment = RfidCardAssignment::query()->where('rfid_card_id', $card->id)->first();

    expect($card->fresh()->status)->toBe(RfidCardStatus::Returned)
        ->and($card->fresh()->member_id)->toBeNull()
        ->and($member->fresh()->rfid_card)->toBeNull()
        ->and($assignment)->not->toBeNull()
        ->and($assignment->status)->toBe(RfidCardStatus::Returned)
        ->and($assignment->return_date)->not->toBeNull()
        ->and(RfidCardAssignment::query()->count())->toBe(1);
});

it('does not assign a lost or blocked card', function (RfidCardStatus $status) {
    $member = Member::factory()->create();
    $card = RfidCard::factory()->create([
        'status' => $status,
        'member_id' => null,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect();

    expect($card->fresh()->status)->toBe($status)
        ->and($card->fresh()->member_id)->toBeNull()
        ->and(RfidCardAssignment::query()->count())->toBe(0);
})->with([
    'lost' => RfidCardStatus::Lost,
    'blocked' => RfidCardStatus::Blocked,
]);

it('denies rfid management without permission', function () {
    $staff = User::factory()->create(['username' => 'staffuser', 'is_active' => true]);
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->post(route('admin.rfid-cards.store'), [
            'card_number' => 'RFIDNOACCESS',
        ])
        ->assertForbidden();
});
