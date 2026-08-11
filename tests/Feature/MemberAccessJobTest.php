<?php

use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Jobs\MemberAccessJob;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\MemberDeviceAccessService;
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

it('queues device user sync when assigning an rfid card', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20001',
        'name' => 'Sahadat Hossain',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => '1233447',
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect(ZktecoCommand::query()->count())->toBe(1);

    $command = ZktecoCommand::query()->first();

    expect($command->serial_number)->toBe('JJA1254800833')
        ->and($command->command)->toBe("DATA UPDATE user Pin=M20001\tName=Sahadat Hossain\tCardNo=1233447\tPri=0\tGrp=1");
});

it('queues device user sync when replacing a member card', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20002',
        'name' => 'Asma Khan',
        'rfid_card' => '9999999',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    RfidCard::factory()->create([
        'card_number' => '9999999',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.replace'), [
            'member_id' => $member->id,
            'card_number' => '1233447',
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))
        ->toContain('Pin=M20002')
        ->toContain('CardNo=1233447');
});

it('dispatches member access job after card assignment', function () {
    Queue::fake();

    $member = Member::factory()->create([
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    Queue::assertPushed(MemberAccessJob::class, fn (MemberAccessJob $job): bool => $job->memberId === $member->id);
});

it('skips device sync when no active devices exist', function () {
    $member = Member::factory()->create([
        'member_code' => 'M20003',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => '1233447',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
    ]);

    expect(app(MemberDeviceAccessService::class)->grantMemberAccess($member->id))->toBeFalse()
        ->and(ZktecoCommand::query()->count())->toBe(0);
});

it('clears prior access removal records when granting access again', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->create([
        'member_code' => 'M20004',
        'name' => 'Renewed Member',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'card_number' => '1233447',
        'status' => RfidCardStatus::Active,
        'member_id' => $member->id,
    ]);

    $member->zktecoAccessRemovals()->create([
        'serial_number' => 'JJA1254800833',
        'revoked_at' => now()->subDay(),
    ]);

    app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);

    expect($member->zktecoAccessRemovals()->count())->toBe(0)
        ->and(ZktecoCommand::query()->count())->toBe(1);
});
