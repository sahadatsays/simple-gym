<?php

use App\Models\ZktecoCommand;
use App\Services\ZktecoCommandCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('purges previous-day terminal commands and preserves pending and today commands', function () {
    $oldCompleted = createHistoricalCommand([
        'command' => 'REBOOT',
        'status' => 'completed',
        'created_at' => now()->subDay()->setTime(10, 0),
    ]);

    $oldFailed = createHistoricalCommand([
        'command' => 'CLEAR LOG',
        'status' => 'failed',
        'created_at' => now()->subDays(2)->setTime(8, 0),
    ]);

    $oldPending = createHistoricalCommand([
        'command' => 'DATA DELETE user Pin=12',
        'status' => 'pending',
        'created_at' => now()->subDay()->setTime(9, 0),
    ]);

    $todayCompleted = createHistoricalCommand([
        'command' => 'CLEAR USER',
        'status' => 'completed',
        'created_at' => now()->setTime(1, 0),
    ]);

    $result = app(ZktecoCommandCleanupService::class)->purgePreviousDays();

    expect($result['deleted'])->toBe(2)
        ->and($result['preserved_pending'])->toBe(1)
        ->and(ZktecoCommand::query()->find($oldCompleted->id))->toBeNull()
        ->and(ZktecoCommand::query()->find($oldFailed->id))->toBeNull()
        ->and(ZktecoCommand::query()->find($oldPending->id))->not->toBeNull()
        ->and(ZktecoCommand::query()->find($todayCompleted->id))->not->toBeNull();
});

it('can include pending commands when explicitly requested', function () {
    createHistoricalCommand([
        'command' => 'REBOOT',
        'status' => 'pending',
        'created_at' => now()->subDay(),
    ]);

    $result = app(ZktecoCommandCleanupService::class)->purgeOlderThan(
        now()->startOfDay(),
        preservePending: false,
    );

    expect($result['deleted'])->toBe(1)
        ->and(ZktecoCommand::query()->count())->toBe(0);
});

/**
 * @param  array{command: string, status: string, created_at: Carbon, serial_number?: string}  $attributes
 */
function createHistoricalCommand(array $attributes): ZktecoCommand
{
    $createdAt = $attributes['created_at'];

    $command = ZktecoCommand::query()->create([
        'serial_number' => $attributes['serial_number'] ?? 'SN-CLEAN-1',
        'command' => $attributes['command'],
        'status' => $attributes['status'],
    ]);

    $command->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();

    return $command->fresh();
}
