<?php

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
        'status' => 'active',
        'last_seen_at' => now(),
    ]);
});

it('requires authentication for device management routes', function () {
    $this->get('/zkteco/devices')->assertRedirect(route('login'));
});

it('lists registered devices', function () {
    $this->actingAs($this->user)
        ->getJson('/zkteco/devices')
        ->assertSuccessful()
        ->assertJsonPath('data.0.serial_number', 'JJA1254800833');
});

it('queues a reboot command', function () {
    $this->actingAs($this->user)
        ->postJson('/zkteco/devices/'.$this->device->id.'/reboot')
        ->assertCreated()
        ->assertJsonPath('data.command', 'REBOOT')
        ->assertJsonPath('data.status', 'pending');

    expect(ZktecoCommand::query()->where('serial_number', $this->device->serial_number)->count())->toBe(1);
});

it('queues a restart command', function () {
    $this->actingAs($this->user)
        ->postJson('/zkteco/devices/'.$this->device->id.'/restart')
        ->assertCreated()
        ->assertJsonPath('data.command', 'RESTART');
});

it('queues a delete user command', function () {
    $this->actingAs($this->user)
        ->deleteJson('/zkteco/devices/'.$this->device->id.'/users/1005')
        ->assertCreated()
        ->assertJsonPath('data.command', 'DATA DELETE USERINFO PIN=1005');
});

it('queues a user upsert command', function () {
    $this->actingAs($this->user)
        ->postJson('/zkteco/devices/'.$this->device->id.'/users', [
            'uid' => 1,
            'user_id' => '1005',
            'name' => 'Asma',
            'privilege' => 0,
            'card_number' => '123456',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    expect(ZktecoCommand::query()->first()->command)
        ->toContain('DATA UPDATE USERINFO')
        ->toContain('PIN=1005')
        ->toContain('Name=Asma');
});

it('returns not found for an invalid device id', function () {
    $this->actingAs($this->user)
        ->postJson('/zkteco/devices/999/reboot')
        ->assertNotFound();
});

it('validates user upsert payload', function () {
    $this->actingAs($this->user)
        ->post('/zkteco/devices/'.$this->device->id.'/users', [], [
            'Accept' => 'application/json',
        ])
        ->assertSessionHasErrors(['user_id']);
});
