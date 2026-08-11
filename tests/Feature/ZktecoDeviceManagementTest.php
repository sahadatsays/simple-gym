<?php

use App\Enums\ZktecoDeviceStatus;
use App\Models\User;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => true]);
    $this->user->assignRole('super-admin');

    $this->device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => ZktecoDeviceStatus::Active,
        'last_seen_at' => now(),
    ]);
});

it('requires authentication for device management routes', function () {
    $this->get(route('admin.zkteco-devices.index'))->assertRedirect(route('login'));
});

it('lists registered devices in the admin panel', function () {
    $this->actingAs($this->user)
        ->get(route('admin.zkteco-devices.index'))
        ->assertSuccessful()
        ->assertSee('JJA1254800833')
        ->assertSee('ZKTeco Devices');
});

it('shows a device detail page', function () {
    $this->actingAs($this->user)
        ->get(route('admin.zkteco-devices.show', $this->device))
        ->assertSuccessful()
        ->assertSee('JJA1254800833')
        ->assertSee('Command Queue');
});

it('approves a pending device', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'PENDING123',
        'status' => ZktecoDeviceStatus::Pending,
    ]);

    $this->actingAs($this->user)
        ->patch(route('admin.zkteco-devices.approve', $device))
        ->assertRedirect(route('admin.zkteco-devices.show', $device));

    expect($device->fresh()->status)->toBe(ZktecoDeviceStatus::Active);
});

it('suspends an active device', function () {
    $this->actingAs($this->user)
        ->patch(route('admin.zkteco-devices.suspend', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device));

    expect($this->device->fresh()->status)->toBe(ZktecoDeviceStatus::Suspended);
});

it('queues a reboot command', function () {
    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.reboot', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device));

    $command = ZktecoCommand::query()->where('serial_number', $this->device->serial_number)->first();

    expect($command)->not->toBeNull()
        ->and($command->command)->toBe('REBOOT')
        ->and($command->status)->toBe('pending');
});

it('queues a restart command', function () {
    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.restart', $this->device))
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device));

    expect(ZktecoCommand::query()->first()->command)->toBe('RESTART');
});

it('queues a delete user command', function () {
    $this->actingAs($this->user)
        ->delete(route('admin.zkteco-devices.users.destroy', $this->device), [
            'user_id' => '1005',
        ])
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device));

    expect(ZktecoCommand::query()->first()->command)->toBe('DATA DELETE user Pin=1005');
});

it('queues a user upsert command', function () {
    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.users.store', $this->device), [
            'uid' => 1,
            'user_id' => '1005',
            'name' => 'Asma',
            'privilege' => 0,
            'card_number' => '123456',
        ])
        ->assertRedirect(route('admin.zkteco-devices.show', $this->device));

    expect(ZktecoCommand::query()->first()->command)
        ->toBe("DATA UPDATE user Pin=1005\tName=Asma\tCardNo=123456\tPri=0\tGrp=1");
});

it('returns not found for an invalid device id', function () {
    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.reboot', ['device' => 999]))
        ->assertNotFound();
});

it('validates user upsert payload', function () {
    $this->actingAs($this->user)
        ->post(route('admin.zkteco-devices.users.store', $this->device), [])
        ->assertSessionHasErrors(['user_id']);
});

it('filters devices by pending status', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'PENDING999',
        'status' => ZktecoDeviceStatus::Pending,
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.zkteco-devices.index', ['status' => 'pending']))
        ->assertSuccessful()
        ->assertSee('PENDING999')
        ->assertDontSee('JJA1254800833');
});
