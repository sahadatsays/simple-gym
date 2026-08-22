<?php

use App\Models\ZktecoCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('clears previous-day device commands via artisan', function () {
    createCommandAt([
        'command' => 'REBOOT',
        'status' => 'completed',
        'created_at' => now()->subDay(),
    ]);

    createCommandAt([
        'command' => 'CLEAR LOG',
        'status' => 'pending',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('zkteco:clear-commands')
        ->expectsOutputToContain('Deleted 1 historical ZKTeco command(s)')
        ->expectsOutputToContain('Pending commands preserved: 1')
        ->assertSuccessful();

    expect(ZktecoCommand::query()->count())->toBe(1)
        ->and(ZktecoCommand::query()->value('status'))->toBe('pending');
});

it('supports dry-run without deleting commands', function () {
    createCommandAt([
        'command' => 'REBOOT',
        'status' => 'failed',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('zkteco:clear-commands', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run: 1 command(s) would be deleted')
        ->assertSuccessful();

    expect(ZktecoCommand::query()->count())->toBe(1);
});

it('rejects invalid before dates', function () {
    $this->artisan('zkteco:clear-commands', ['--before' => 'yesterday'])
        ->assertFailed();
});

it('is scheduled to run nightly', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('zkteco:clear-commands');
});

/**
 * @param  array{command: string, status: string, created_at: Carbon, serial_number?: string}  $attributes
 */
function createCommandAt(array $attributes): ZktecoCommand
{
    $createdAt = $attributes['created_at'];

    $command = ZktecoCommand::query()->create([
        'serial_number' => $attributes['serial_number'] ?? 'SN-CMD-1',
        'command' => $attributes['command'],
        'status' => $attributes['status'],
    ]);

    $command->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();

    return $command->fresh();
}
