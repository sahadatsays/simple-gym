<?php

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Enums\RfidCardStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Jobs\MemberAccessJob;
use App\Jobs\MemberAccessRestrictionEndJob;
use App\Jobs\MemberAccessRestrictionStartJob;
use App\Models\GymSetting;
use App\Models\Member;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\GymSettingService;
use App\Services\MemberAccessRestrictionService;
use App\Services\MemberDeviceAccessPolicy;
use App\Services\MemberDeviceAccessService;
use App\Support\MemberAccessRestrictionWindow;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    config(['queue.default' => 'sync']);

    $this->settings = GymSetting::query()->firstOrFail();
    $this->settings->update([
        'timezone' => 'Asia/Dhaka',
    ]);

    app(GymSettingService::class)->update([
        'timezone' => 'Asia/Dhaka',
    ]);

    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
    ]);
});

function enableMaleAccessRestriction(string $start = '18:00', string $end = '22:00'): void
{
    $settings = GymSetting::query()->firstOrFail();

    $settings->update([
        'member_access_restriction_enabled' => true,
        'member_access_restriction_start_time' => $start,
        'member_access_restriction_end_time' => $end,
        'member_access_restriction_group' => 'male',
    ]);

    app(GymSettingService::class)->update([
        'member_access_restriction_enabled' => true,
        'member_access_restriction_start_time' => $start,
        'member_access_restriction_end_time' => $end,
        'member_access_restriction_group' => 'male',
    ]);
}

function travelToRestriction(string $time): void
{
    Carbon::setTestNow(Carbon::parse('2026-08-12 '.$time, 'Asia/Dhaka'));
}

function createMemberWithActiveCard(array $memberAttributes = [], array $cardAttributes = []): array
{
    $member = Member::factory()->create(array_merge([
        'member_code' => 'M'.fake()->unique()->numerify('#####'),
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ], $memberAttributes));

    $card = RfidCard::factory()->create(array_merge([
        'member_id' => $member->id,
        'status' => RfidCardStatus::Active,
        'assigned_at' => now(),
    ], $cardAttributes));

    $member->update(['rfid_card' => $card->card_number]);

    return [$member->fresh(['activeRfidCard']), $card->fresh()];
}

it('blocks male members from syncing while the restriction window is active', function () {
    enableMaleAccessRestriction();
    travelToRestriction('19:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Male,
        'name' => 'Restricted Male',
    ]);

    expect(app(MemberDeviceAccessPolicy::class)->canSyncToDevice($member))->toBeFalse();

    $synced = app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);

    expect($synced)->toBeFalse()
        ->and(ZktecoCommand::query()->count())->toBe(0);
});

it('allows female members to sync while the restriction window is active', function () {
    enableMaleAccessRestriction();
    travelToRestriction('19:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Female,
        'name' => 'Allowed Female',
    ]);

    expect(app(MemberDeviceAccessPolicy::class)->canSyncToDevice($member))->toBeTrue();

    $synced = app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);

    expect($synced)->toBeTrue()
        ->and(ZktecoCommand::query()->count())->toBe(1);
});

it('removes active male members from devices when the restriction starts', function () {
    enableMaleAccessRestriction();
    travelToRestriction('17:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Male,
    ]);

    app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);

    expect(ZktecoCommand::query()->where('command', 'like', 'DATA UPDATE%')->count())->toBe(1);

    ZktecoCommand::query()->delete();

    travelToRestriction('18:00');

    $processed = app(MemberAccessRestrictionService::class)->applyRestrictionStart();

    expect($processed)->toBe(1)
        ->and(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toBe('DATA DELETE user Pin='.$card->id);
});

it('restores eligible male members when the restriction ends', function () {
    enableMaleAccessRestriction();
    travelToRestriction('18:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Male,
        'name' => 'Restore Male',
    ]);

    app(MemberAccessRestrictionService::class)->applyRestrictionStart();

    travelToRestriction('22:00');

    $processed = app(MemberAccessRestrictionService::class)->applyRestrictionEnd();

    expect($processed)->toBe(1)
        ->and(ZktecoCommand::query()->latest('id')->value('command'))
        ->toBe("DATA UPDATE user Pin={$card->id}\tName=Restore Male\tCardID={$card->card_number}\tPri=0\tGrp=1");
});

it('does not restore expired male members after the restriction ends', function () {
    enableMaleAccessRestriction();
    travelToRestriction('18:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Male,
        'membership_expires_at' => now()->subDay(),
        'status' => MemberStatus::Expired,
    ]);

    app(MemberAccessRestrictionService::class)->applyRestrictionStart();

    travelToRestriction('22:00');

    $processed = app(MemberAccessRestrictionService::class)->applyRestrictionEnd();

    expect($processed)->toBe(0)
        ->and(ZktecoCommand::query()->where('command', 'like', 'DATA UPDATE%')->count())->toBe(0);
});

it('does not sync a newly assigned male card to the device during restriction', function () {
    enableMaleAccessRestriction();
    travelToRestriction('19:00');

    Queue::fake([MemberAccessJob::class]);

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super-admin');

    $member = Member::factory()->create([
        'gender' => Gender::Male,
        'member_code' => 'M90001',
        'status' => MemberStatus::Active,
        'membership_expires_at' => now()->addMonth(),
    ]);

    $card = RfidCard::factory()->create([
        'status' => RfidCardStatus::Unassigned,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.rfid-cards.assign', $card), [
            'member_id' => $member->id,
        ])
        ->assertRedirect(route('admin.rfid-cards.index'));

    Queue::assertPushed(MemberAccessJob::class);

    (new MemberAccessJob($member->id))->handle(app(MemberDeviceAccessService::class));

    expect(ZktecoCommand::query()->count())->toBe(0);
});

it('restores access when a male member becomes female during an active restriction', function () {
    enableMaleAccessRestriction();
    travelToRestriction('19:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Male,
        'name' => 'Gender Change',
    ]);

    app(MemberAccessRestrictionService::class)->applyRestrictionStart();
    ZktecoCommand::query()->delete();

    $member->update(['gender' => Gender::Female]);
    app(MemberDeviceAccessService::class)->reconcileMemberDeviceAccess($member->id);

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toContain('DATA UPDATE user');
});

it('removes access when a female member becomes male during an active restriction', function () {
    enableMaleAccessRestriction();
    travelToRestriction('19:00');

    [$member, $card] = createMemberWithActiveCard([
        'gender' => Gender::Female,
        'name' => 'Female To Male',
    ]);

    app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);
    ZktecoCommand::query()->delete();

    $member->update(['gender' => Gender::Male]);
    app(MemberDeviceAccessService::class)->reconcileMemberDeviceAccess($member->id);

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('command'))->toBe('DATA DELETE user Pin='.$card->id);
});

it('is idempotent when the restriction start job runs twice', function () {
    enableMaleAccessRestriction();
    travelToRestriction('17:00');

    [$member] = createMemberWithActiveCard([
        'gender' => Gender::Male,
    ]);

    app(MemberDeviceAccessService::class)->grantMemberAccess($member->id);
    ZktecoCommand::query()->delete();

    travelToRestriction('18:00');

    $service = app(MemberAccessRestrictionService::class);

    expect($service->applyRestrictionStart())->toBe(1)
        ->and($service->applyRestrictionStart())->toBe(0)
        ->and(ZktecoCommand::query()->count())->toBe(1);
});

it('is idempotent when the restriction end job runs twice', function () {
    enableMaleAccessRestriction();
    travelToRestriction('18:00');

    [$member] = createMemberWithActiveCard([
        'gender' => Gender::Male,
    ]);

    app(MemberAccessRestrictionService::class)->applyRestrictionStart();

    travelToRestriction('22:00');

    $service = app(MemberAccessRestrictionService::class);

    expect($service->applyRestrictionEnd())->toBe(1)
        ->and($service->applyRestrictionEnd())->toBe(0)
        ->and(ZktecoCommand::query()->where('command', 'like', 'DATA UPDATE%')->count())->toBe(1);
});

it('evaluates overnight restriction windows correctly', function () {
    enableMaleAccessRestriction('22:00', '06:00');

    $window = app(MemberAccessRestrictionWindow::class);
    $settings = GymSetting::query()->firstOrFail();

    Carbon::setTestNow(Carbon::parse('2026-08-12 23:00:00', 'Asia/Dhaka'));
    expect($window->isActive($settings))->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-08-13 05:00:00', 'Asia/Dhaka'));
    expect($window->isActive($settings))->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-08-13 12:00:00', 'Asia/Dhaka'));
    expect($window->isActive($settings))->toBeFalse();
});

it('dispatches boundary jobs only once per configured time', function () {
    enableMaleAccessRestriction();

    Queue::fake();

    travelToRestriction('18:00');

    $this->artisan('access:restriction:dispatch')->assertSuccessful();
    $this->artisan('access:restriction:dispatch')->assertSuccessful();

    Queue::assertPushed(MemberAccessRestrictionStartJob::class, 1);

    travelToRestriction('22:00');

    $this->artisan('access:restriction:dispatch')->assertSuccessful();
    $this->artisan('access:restriction:dispatch')->assertSuccessful();

    Queue::assertPushed(MemberAccessRestrictionEndJob::class, 1);
});

afterEach(function () {
    Carbon::setTestNow();
});
