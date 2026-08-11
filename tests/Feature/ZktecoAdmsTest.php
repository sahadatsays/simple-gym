<?php

use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a plain text registry response and registers a device', function () {
    $response = $this->get('/iclock/registry?SN=JJA1254800833&pushver=3.0.1');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    expect($response->getContent())->toContain("RegistryCode=0\r\n")
        ->and($response->getContent())->toContain('ServerName=ADMS_Laravel');

    $device = ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->first();

    expect($device)->not->toBeNull()
        ->and($device->status)->toBe('active')
        ->and($device->last_seen_at)->not->toBeNull();
});

it('does not create duplicate devices on repeated registry requests', function () {
    $this->get('/iclock/registry?SN=JJA1254800833')->assertSuccessful();

    $firstSeenAt = ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->value('last_seen_at');

    $this->travel(1)->minute();

    $this->get('/iclock/registry?SN=JJA1254800833')->assertSuccessful();

    expect(ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->count())->toBe(1)
        ->and(ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->value('last_seen_at'))
        ->not->toEqual($firstSeenAt);
});

it('returns a plain text push response and updates last seen', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
        'last_seen_at' => now()->subHour(),
    ]);

    $response = $this->get('/iclock/push?SN=JJA1254800833');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    expect($response->getContent())->toContain("RegistryCode=0\r\n")
        ->and($response->getContent())->toContain('ServerName=ADMS_Laravel');

    expect($device->fresh()->last_seen_at?->greaterThan(now()->subMinute()))->toBeTrue();
});

it('delivers a pending command on push and marks it as sent', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    $response = $this->get('/iclock/push?SN=JJA1254800833');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('C:'.$command->id.':REBOOT');

    $command->refresh();

    expect($command->status)->toBe('sent')
        ->and($command->sent_at)->not->toBeNull();
});

it('acknowledges a command from a push post request', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->post('/iclock/push?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    $command->refresh();

    expect($command->status)->toBe('acknowledged')
        ->and($command->return_code)->toBe(0)
        ->and($command->acknowledged_at)->not->toBeNull();
});
