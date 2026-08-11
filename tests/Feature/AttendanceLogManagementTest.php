<?php

use App\Models\AttendanceLog;
use App\Models\Member;
use App\Models\User;
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
    ]);
});

it('requires authentication for the attendance logs index', function () {
    $this->get(route('admin.attendance-logs.index'))->assertRedirect(route('login'));
});

it('lists attendance logs in the admin panel', function () {
    AttendanceLog::query()->create([
        'sn' => 'JJA1254800833',
        'user_id' => '1005',
        'timestamp' => '2026-08-11 10:00:00',
        'punch_status' => '0',
        'verify_mode' => '4',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.attendance-logs.index'))
        ->assertSuccessful()
        ->assertSee('Attendance Logs')
        ->assertSee('1005')
        ->assertSee('JJA1254800833')
        ->assertSee('Check In')
        ->assertSee('Card Scan');
});

it('links attendance logs to members when the PIN matches member code', function () {
    $member = Member::factory()->create([
        'member_code' => '1005',
        'name' => 'Asma Khan',
    ]);

    AttendanceLog::query()->create([
        'sn' => 'JJA1254800833',
        'user_id' => '1005',
        'timestamp' => '2026-08-11 10:00:00',
        'punch_status' => '0',
        'verify_mode' => '1',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.attendance-logs.index'))
        ->assertSuccessful()
        ->assertSee('Asma Khan')
        ->assertSee(route('admin.members.show', $member), false);
});

it('filters attendance logs by device serial and date range', function () {
    AttendanceLog::query()->create([
        'sn' => 'JJA1254800833',
        'user_id' => '1005',
        'timestamp' => '2026-08-11 10:00:00',
        'punch_status' => '0',
        'verify_mode' => '1',
    ]);

    AttendanceLog::query()->create([
        'sn' => 'OTHER1234567',
        'user_id' => '2001',
        'timestamp' => '2026-08-12 11:00:00',
        'punch_status' => '1',
        'verify_mode' => '4',
    ]);

    $this->actingAs($this->user)
        ->get(route('admin.attendance-logs.index', [
            'sn' => 'JJA1254800833',
            'from_date' => '2026-08-11',
            'to_date' => '2026-08-11',
        ]))
        ->assertSuccessful()
        ->assertSee('1005')
        ->assertDontSee('2001');
});

it('denies attendance logs access without permission', function () {
    $staff = User::factory()->create(['is_active' => true]);
    $staff->assignRole('trainer');

    $this->actingAs($staff)
        ->get(route('admin.attendance-logs.index'))
        ->assertForbidden();
});
