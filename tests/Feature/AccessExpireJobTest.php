<?php

use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Jobs\AccessExpireJob;
use App\Models\Member;
use App\Models\MemberZktecoAccessRemoval;
use App\Models\RfidCard;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\MemberDeviceAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues device user removal for expired members with assigned cards', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->expired()->create([
        'member_code' => 'M10001',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->subDay(),
    ]);

    $card = RfidCard::factory()->create([
        'member_id' => $member->id,
        'card_number' => '1233447',
        'status' => RfidCardStatus::Active,
    ]);

    app(MemberDeviceAccessService::class)->revokeExpiredMemberAccess();

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toBe('DATA DELETE user Pin='.$card->id)
        ->and(ZktecoCommand::query()->value('serial_number'))->toBe($device->serial_number)
        ->and(MemberZktecoAccessRemoval::query()->count())->toBe(1)
        ->and($member->fresh()->status)->toBe(MemberStatus::Expired)
        ->and($member->fresh()->activeRfidCard)->toBeNull();
});

it('does not queue duplicate removal commands for the same expired member', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->expired()->create([
        'member_code' => 'M10002',
        'status' => MemberStatus::Expired,
        'membership_expires_at' => now()->subDays(3),
    ]);

    RfidCard::factory()->create([
        'member_id' => $member->id,
        'status' => RfidCardStatus::Active,
    ]);

    $service = app(MemberDeviceAccessService::class);

    $service->revokeExpiredMemberAccess();
    $service->revokeExpiredMemberAccess();

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(MemberZktecoAccessRemoval::query()->count())->toBe(1);
});

it('skips active members', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    Member::factory()->create([
        'member_code' => 'M10003',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    app(MemberDeviceAccessService::class)->revokeExpiredMemberAccess();

    expect(ZktecoCommand::query()->count())->toBe(0)
        ->and(MemberZktecoAccessRemoval::query()->count())->toBe(0);
});

it('handles missing active devices safely', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Suspended,
    ]);

    Member::factory()->expired()->create([
        'member_code' => 'M10004',
        'membership_expires_at' => now()->subDay(),
    ]);

    app(MemberDeviceAccessService::class)->revokeExpiredMemberAccess();

    expect(ZktecoCommand::query()->count())->toBe(0)
        ->and(MemberZktecoAccessRemoval::query()->count())->toBe(0);
});

it('dispatches the access expire job from the artisan command', function () {
    Queue::fake();

    $this->artisan('access:expire')->assertSuccessful();

    Queue::assertPushed(AccessExpireJob::class);
});

it('processes expired members through the queued job', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);

    $member = Member::factory()->expired()->create([
        'member_code' => 'M10005',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->subDays(2),
    ]);

    RfidCard::factory()->create([
        'member_id' => $member->id,
        'status' => RfidCardStatus::Active,
    ]);

    (new AccessExpireJob)->handle(app(MemberDeviceAccessService::class));

    expect(ZktecoCommand::query()->count())->toBe(1);
});
