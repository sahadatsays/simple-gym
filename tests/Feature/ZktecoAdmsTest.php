<?php

use App\Enums\ZktecoDeviceStatus;
use App\Models\AttendanceLog;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('returns a plain text registry response and registers a device', function () {
    $response = $this->get('/iclock/registry?SN=JJA1254800833&pushver=3.0.1');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    expect($response->getContent())->toContain("RegistryCode=0\r\n")
        ->and($response->getContent())->toContain('ServerName=ADMS_Laravel');

    $device = ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->first();

    expect($device)->not->toBeNull()
        ->and($device->status)->toBe(ZktecoDeviceStatus::Active)
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
        ->and($response->getContent())->toContain('ServerName=ADMS_Laravel')
        ->and($response->getContent())->not->toContain('C:');

    expect($device->fresh()->last_seen_at?->greaterThan(now()->subMinute()))->toBeTrue();
});

it('does not acknowledge a command from push post', function () {
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

    expect($command->status)->toBe('sent')
        ->and($command->acknowledged_at)->toBeNull();
});

it('returns a cdata config block on get handshake and registers a pending device', function () {
    $response = $this->get('/iclock/cdata?SN=JJA1254800833&options=all&pushver=3.0.1');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    expect($response->getContent())->toContain("GET OPTION FROM: JJA1254800833\r\n")
        ->and($response->getContent())->toContain("ATTLOGStamp=None\r\n")
        ->and($response->getContent())->toContain('AttLogFunOn=1')
        ->and($response->getContent())->toContain('ServerVersion=2.4.1')
        ->and($response->getContent())->toContain('PushVersion=2.0.33S')
        ->and($response->getContent())->toContain('Realtime=1');

    $device = ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->first();

    expect($device)->not->toBeNull()
        ->and($device->status)->toBe(ZktecoDeviceStatus::Pending)
        ->and($device->last_seen_at)->not->toBeNull();
});

it('returns saved stamps in the cdata config block', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
        'stamps' => ['ATTLOG' => '2026-08-11T10:00:00'],
    ]);

    $response = $this->get('/iclock/cdata?SN=JJA1254800833&options=all&pushver=3.0.1');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('ATTLOGStamp=2026-08-11T10:00:00');
});

it('rejects cdata uploads from pending devices', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'pending',
    ]);

    $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833&table=ATTLOG&Stamp=9999',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        "1005\t2026-08-11 10:00:00\t0\t1\r\n",
    )
        ->assertStatus(503)
        ->assertSee('Device pending approval.');

    expect(AttendanceLog::query()->count())->toBe(0);
});

it('ingests attlog data from an approved device and advances the stamp', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $response = $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833&table=ATTLOG&Stamp=2026-08-11T10:00:00',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        "1005\t2026-08-11 10:00:00\t0\t1\r\n1006\t2026-08-11 11:00:00\t1\t15\r\n",
    );

    $response->assertSuccessful();
    expect($response->getContent())->toBe("OK\n");

    expect(AttendanceLog::query()->count())->toBe(2);

    $attendance = AttendanceLog::query()->where('pim', '1005')->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->sn)->toBe('JJA1254800833')
        ->and($attendance->punch_status)->toBe('0')
        ->and($attendance->verify_mode)->toBe('1');

    $device = ZktecoDevice::query()->where('serial_number', 'JJA1254800833')->first();

    expect($device->stamps)->toBe(['ATTLOG' => '2026-08-11T10:00:00']);
});

it('captures attlog payloads without an explicit table query parameter', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    Log::spy();

    $response = $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        "1005\t2026-08-11 10:00:00\t0\t4\r\n",
    );

    $response->assertSuccessful();
    expect($response->getContent())->toBe("OK\n");

    Log::shouldHaveReceived('info')
        ->once()
        ->with('[ATT_DUMP]', Mockery::on(function (array $context): bool {
            return $context['serial_number'] === 'JJA1254800833'
                && str_contains($context['content'], '2026-08-11 10:00:00');
        }));

    expect(AttendanceLog::query()->count())->toBe(1);

    $attendance = AttendanceLog::query()->first();

    expect($attendance->pim)->toBe('1005')
        ->and($attendance->verify_mode)->toBe('4');
});

it('ingests f22 key value attlog lines', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $line = "time=2026-08-11 21:47:45\tpin=1\tcardno=1233447\teventaddr=1\tevent=0\tinoutstatus=0\tverifytype=4\tindex=353";

    $response = $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833&table=ATTLOG',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        $line,
    );

    $response->assertSuccessful();
    expect($response->getContent())->toBe("OK\n");
    expect(AttendanceLog::query()->count())->toBe(1);

    $attendance = AttendanceLog::query()->first();

    expect($attendance->pim)->toBe('1')
        ->and($attendance->timestamp->format('Y-m-d H:i:s'))->toBe('2026-08-11 21:47:45')
        ->and($attendance->punch_status)->toBe('0')
        ->and($attendance->verify_mode)->toBe('4');
});

it('stores failed card verifications separately from successful attendance logs', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $body = implode("\n", [
        "time=2026-08-11 21:47:45\tpin=1\tcardno=1233447\tverifytype=4\tinoutstatus=0",
        "time=2026-08-11 21:48:00\tpin=1\tcardno=\tverifytype=0\tinoutstatus=0",
    ]);

    $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833&table=ATTLOG',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        $body,
    )->assertSuccessful();

    expect(AttendanceLog::query()->count())->toBe(1)
        ->and(AttendanceLog::query()->first()->verify_mode)->toBe('4')
        ->and(DB::table('attendance_log_failures')->count())->toBe(1)
        ->and(DB::table('attendance_log_failures')->value('verify_mode'))->toBe('0');
});

it('accepts unknown cdata tables as a no-op', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $this->call(
        'POST',
        '/iclock/cdata?SN=JJA1254800833&table=UNKNOWN&Stamp=9999',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'text/plain'],
        'payload',
    )
        ->assertSuccessful()
        ->assertSee('OK');

    expect(ZktecoDevice::query()->first()->stamps)->toBeNull();
});

it('returns ok from getrequest when serial number is missing', function () {
    $this->get('/iclock/getrequest')
        ->assertSuccessful()
        ->assertSee('OK');
});

it('returns ok from getrequest for an unknown device', function () {
    $this->get('/iclock/getrequest?SN=UNKNOWN123')
        ->assertSuccessful()
        ->assertSee('OK');

    expect(ZktecoDevice::query()->where('serial_number', 'UNKNOWN123')->exists())->toBeFalse();
});

it('delivers a pending command on getrequest and marks it as sent', function () {
    $device = ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
        'last_seen_at' => now()->subHour(),
    ]);

    $command = ZktecoCommand::query()->create([
        'serial_number' => 'JJA1254800833',
        'command' => 'REBOOT',
        'status' => 'pending',
    ]);

    $response = $this->get('/iclock/getrequest?SN=JJA1254800833');

    $response->assertSuccessful();
    expect($response->getContent())->toContain('C:'.$command->id.':REBOOT');

    $command->refresh();

    expect($command->status)->toBe('sent')
        ->and($command->sent_at)->not->toBeNull()
        ->and($device->fresh()->last_seen_at?->greaterThan(now()->subMinute()))->toBeTrue();
});

it('returns ok from getrequest when no command is pending', function () {
    ZktecoDevice::query()->create([
        'serial_number' => 'JJA1254800833',
        'status' => 'active',
    ]);

    $this->get('/iclock/getrequest?SN=JJA1254800833')
        ->assertSuccessful()
        ->assertSee('OK');
});
