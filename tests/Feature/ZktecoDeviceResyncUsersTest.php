<?php

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Models\Member;
use App\Models\MemberZktecoAccessRemoval;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\GymSettingService;
use App\Services\ZktecoCommandBuilder;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->user = User::factory()->create(['is_active' => true]);
    $this->user->assignRole('super-admin');

    app(GymSettingService::class)->update([
        'timezone' => 'Asia/Dhaka',
        'member_access_restriction_enabled' => true,
        'member_access_restriction_start_time' => '18:00',
        'member_access_restriction_end_time' => '22:00',
        'member_access_restriction_group' => 'male',
    ]);

    $this->device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
        'last_seen_at' => now(),
    ]);

    $this->otherDevice = ZktecoDevice::query()->create([
        'serial_number' => 'OTHERDEVICE001',
        'status' => ZktecoDeviceStatus::Active,
        'last_seen_at' => now(),
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('shows the resync all users button on an active device', function () {
    $this->actingAs($this->user)
        ->get(route('admin.zkteco-devices.show', $this->device))
        ->assertSuccessful()
        ->assertSee(__('settings.zkteco.resync_all_users'));
});

it('updates every eligible card and removes nobody outside the restriction window', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 12:00', 'Asia/Dhaka'));

    [$male, $maleCard] = createResyncMember(Gender::Male, 'Resync Male');
    [$female, $femaleCard] = createResyncMember(Gender::Female, 'Resync Female');
    [$expired, $expiredCard] = createResyncMember(Gender::Male, 'Expired Male', [
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDay(),
    ]);

    MemberZktecoAccessRemoval::query()->create([
        'member_id' => $male->id,
        'serial_number' => $this->device->serial_number,
        'revoked_at' => now(),
    ]);

    MemberZktecoAccessRemoval::query()->create([
        'member_id' => $male->id,
        'serial_number' => $this->otherDevice->serial_number,
        'revoked_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.resync-users', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device))
        ->assertSessionHas('flash.message', __('settings.zkteco.users_resynced', [
            'synced' => 2,
            'removed' => 0,
        ]));

    $commands = ZktecoCommand::query()->orderBy('id')->get();

    expect($commands)->toHaveCount(2)
        ->and($commands->pluck('serial_number')->unique()->all())->toBe([$this->device->serial_number])
        ->and($commands->pluck('command')->all())->toBe([
            upsertCommand($male, $maleCard),
            upsertCommand($female, $femaleCard),
        ])
        ->and(ZktecoCommand::query()->where('command', 'like', 'DATA DELETE%')->exists())->toBeFalse()
        ->and(MemberZktecoAccessRemoval::query()->where('member_id', $male->id)->where('serial_number', $this->device->serial_number)->exists())->toBeFalse()
        ->and(MemberZktecoAccessRemoval::query()->where('member_id', $male->id)->where('serial_number', $this->otherDevice->serial_number)->exists())->toBeTrue()
        ->and(ZktecoCommand::query()->where('command', 'like', '%Pin='.$expiredCard->id.'%')->exists())->toBeFalse();
});

it('removes male cards and syncs only allowed cards during the restriction window', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 19:00', 'Asia/Dhaka'));

    [$male, $maleCard] = createResyncMember(Gender::Male, 'Blocked Male');
    [$female, $femaleCard] = createResyncMember(Gender::Female, 'Allowed Female');

    MemberZktecoAccessRemoval::query()->create([
        'member_id' => $female->id,
        'serial_number' => $this->device->serial_number,
        'revoked_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.resync-users', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device))
        ->assertSessionHas('flash.message', __('settings.zkteco.users_resynced', [
            'synced' => 1,
            'removed' => 1,
        ]));

    $commands = ZktecoCommand::query()->orderBy('id')->get();

    expect($commands)->toHaveCount(2)
        ->and($commands->pluck('serial_number')->unique()->all())->toBe([$this->device->serial_number])
        ->and($commands[0]->command)->toBe('DATA DELETE user Pin='.$maleCard->id)
        ->and($commands[1]->command)->toBe(upsertCommand($female, $femaleCard))
        ->and(MemberZktecoAccessRemoval::query()->where('member_id', $male->id)->where('serial_number', $this->device->serial_number)->exists())->toBeTrue()
        ->and(MemberZktecoAccessRemoval::query()->where('member_id', $male->id)->where('serial_number', $this->otherDevice->serial_number)->exists())->toBeFalse()
        ->and(MemberZktecoAccessRemoval::query()->where('member_id', $female->id)->exists())->toBeFalse();
});

it('does not queue duplicate commands when resync runs again', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 19:00', 'Asia/Dhaka'));

    createResyncMember(Gender::Male, 'Blocked Male');
    createResyncMember(Gender::Female, 'Allowed Female');

    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.resync-users', $this->device))
        ->assertRedirect();

    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.resync-users', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device))
        ->assertSessionHas('flash.message', __('settings.zkteco.users_resynced', [
            'synced' => 0,
            'removed' => 0,
        ]));

    expect(ZktecoCommand::query()->count())->toBe(2);
});

it('rejects resync for a device that is not active', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 12:00', 'Asia/Dhaka'));

    $this->device->update(['status' => ZktecoDeviceStatus::Suspended]);

    createResyncMember(Gender::Female, 'Allowed Female');

    $this->actingAs($this->user)
        ->from(route('admin.zkteco-devices.show', $this->device))
        ->post(route('admin.zkteco-devices.resync-users', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device))
        ->assertSessionHas('flash.message', 'Only an active device can resync card users.');

    expect(ZktecoCommand::query()->count())->toBe(0);
});

/**
 * @param  array<string, mixed>  $memberAttributes
 * @return array{0: Member, 1: RfidCard}
 */
function createResyncMember(Gender $gender, string $name, array $memberAttributes = []): array
{
    $member = Member::factory()->create(array_merge([
        'name' => $name,
        'gender' => $gender,
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ], $memberAttributes));

    $card = RfidCard::factory()->create([
        'member_id' => $member->id,
        'status' => RfidCardStatus::Active,
        'assigned_at' => now(),
    ]);

    $member->update(['rfid_card' => $card->card_number]);

    return [$member->fresh(['activeRfidCard']), $card->fresh()];
}

function upsertCommand(Member $member, RfidCard $card): string
{
    return app(ZktecoCommandBuilder::class)->upsertUser([
        'pim' => $card->id,
        'name' => $member->name,
        'card_number' => $card->card_number,
        'privilege' => 0,
        'group' => 1,
    ]);
}
