<?php

use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\ZktecoCommandBuilder;
use App\Services\ZktecoDeviceCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('queues a command as pending', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $service = app(ZktecoDeviceCommandService::class);

    $command = $service->queue($device, 'REBOOT');

    expect($command->status)->toBe('pending')
        ->and($command->serial_number)->toBe('JJA1254800833')
        ->and($command->command)->toBe('REBOOT')
        ->and($command->id)->not->toBeNull()
        ->and($command->sent_at)->toBeNull()
        ->and($command->acknowledged_at)->toBeNull();
});

it('delivers the oldest pending command once via getrequest', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $first = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'RESTART',
        'status' => 'pending',
    ]);

    $response = $this->get('/iclock/getrequest?SN=JJA1254800833');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('C:'.$first->id.':REBOOT');

    $first->refresh();

    expect($first->status)->toBe('sent')
        ->and($first->sent_at)->not->toBeNull();

    $secondResponse = $this->get('/iclock/getrequest?SN=JJA1254800833');

    expect($secondResponse->getContent())->toContain('C:')
        ->and($secondResponse->getContent())->not->toContain('C:'.$first->id.':REBOOT');
});

it('does not deliver the same pending command twice on sequential polls', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    $this->get('/iclock/getrequest?SN=JJA1254800833')->assertSuccessful();
    $second = $this->get('/iclock/getrequest?SN=JJA1254800833');

    expect(trim($second->getContent()))->toBe('OK');

    $command->refresh();

    expect($command->status)->toBe('sent');
});

it('does not acknowledge a command immediately after getrequest delivery via push', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    $this->get('/iclock/getrequest?SN=JJA1254800833')->assertSuccessful();

    $command = ZktecoCommand::query()->first();

    expect($command->status)->toBe('sent');

    $this->post('/iclock/push?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    expect($command->fresh()->status)->toBe('sent')
        ->and($command->fresh()->acknowledged_at)->toBeNull();
});

it('acknowledges a sent command through getrequest when the device reports back', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'sent',
        'sent_at' => now()->subMinute(),
    ]);

    $this->get('/iclock/getrequest?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    $command->refresh();

    expect($command->status)->toBe('completed')
        ->and($command->return_code)->toBe(0)
        ->and($command->acknowledged_at)->not->toBeNull();
});

it('ignores acknowledgement for pending commands', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    $this->post('/iclock/devicecmd?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    expect($command->fresh()->status)->toBe('pending')
        ->and($command->fresh()->acknowledged_at)->toBeNull();
});

it('acknowledges a command by id through devicecmd', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->post('/iclock/devicecmd?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful()
        ->assertSee('OK');

    $command->refresh();

    expect($command->status)->toBe('completed')
        ->and($command->return_code)->toBe(0)
        ->and($command->acknowledged_at)->not->toBeNull();
});

it('parses devicecmd raw body with application/push content type', function () {
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

    $response = $this->call(
        'POST',
        '/iclock/devicecmd?SN=JJA1254800833',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/push;charset=UTF-8'],
        'ID='.$command->id.'&Return=0',
    );

    $response->assertSuccessful();
    expect(trim($response->getContent()))->toBe('OK');

    $command->refresh();

    expect($command->status)->toBe('completed')
        ->and($command->return_code)->toBe(0);
});

it('marks a command as failed when return code is non-zero', function () {
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

    $this->post('/iclock/devicecmd?SN=JJA1254800833&ID='.$command->id.'&Return=1')
        ->assertSuccessful();

    $command->refresh();

    expect($command->status)->toBe('failed')
        ->and($command->return_code)->toBe(1);
});

it('prevents a device from acknowledging another devices command', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'DEVICE-A',
        'status' => 'active',
    ]);

    ZktecoDevice::query()->create([
        'serial_number' => 'DEVICE-B',
        'status' => 'active',
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'DEVICE-A',
        'command' => 'REBOOT',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->post('/iclock/devicecmd?SN=DEVICE-B&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    expect($command->fresh()->status)->toBe('sent');
});

it('is idempotent when the same acknowledgement is sent twice', function () {
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

    $this->post('/iclock/devicecmd?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    $acknowledgedAt = $command->fresh()->acknowledged_at;

    $this->travel(1)->minute();

    $this->post('/iclock/devicecmd?SN=JJA1254800833&ID='.$command->id.'&Return=0')
        ->assertSuccessful();

    $command->refresh();

    expect($command->status)->toBe('completed')
        ->and($command->acknowledged_at?->eq($acknowledgedAt))->toBeTrue();
});

it('builds reboot restart delete and upsert command strings', function () {
    $builder = app(ZktecoCommandBuilder::class);

    expect($builder->reboot())->toBe('REBOOT')
        ->and($builder->restart())->toBe('RESTART')
        ->and($builder->deleteUser('1005'))->toBe('DATA DELETE User Pin=1005')
        ->and($builder->upsertUser([
            'user_id' => '1005',
            'name' => 'Asma',
            'privilege' => 0,
            'card_number' => '123456',
        ]))->toBe("DATA USER PIN=1005\tName=Asma\tCard=123456\tPri=0");
});

it('updates last seen when a device polls getrequest', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
        'last_seen_at' => now()->subHour(),
    ]);

    $this->get('/iclock/getrequest?SN=JJA1254800833')->assertSuccessful();

    expect($device->fresh()->last_seen_at?->greaterThan(now()->subMinute()))->toBeTrue();
});
