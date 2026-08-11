<?php

namespace App\Services;

use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ZktecoDeviceCommandService extends BaseService
{
    public function queue(ZktecoDevice $device, string $command): ZktecoCommand
    {
        return $this->transaction(function () use ($device, $command): ZktecoCommand {
            $queued = ZktecoCommand::query()->create([
                'serial_number' => $device->serial_number,
                'command' => $command,
                'status' => 'pending',
            ]);

            Log::info('ZKTeco command queued', [
                'serial_number' => $device->serial_number,
                'command_id' => $queued->id,
                'command' => $command,
            ]);

            return $queued;
        });
    }

    public function getNextPendingCommand(ZktecoDevice $device): ?ZktecoCommand
    {
        return $this->transaction(function () use ($device): ?ZktecoCommand {
            $command = ZktecoCommand::query()
                ->where('serial_number', $device->serial_number)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($command === null) {
                return null;
            }

            return $this->markAsSent($command);
        });
    }

    public function markAsSent(ZktecoCommand $command): ZktecoCommand
    {
        $command->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Log::info('ZKTeco command sent to device', [
            'serial_number' => $command->serial_number,
            'command_id' => $command->id,
        ]);

        return $command->fresh();
    }

    public function acknowledge(
        ZktecoDevice $device,
        int $commandId,
        int $returnCode,
    ): ZktecoCommand {
        return $this->transaction(function () use ($device, $commandId, $returnCode): ZktecoCommand {
            $command = ZktecoCommand::query()
                ->whereKey($commandId)
                ->where('serial_number', $device->serial_number)
                ->lockForUpdate()
                ->first();

            if ($command === null) {
                throw (new ModelNotFoundException)->setModel(ZktecoCommand::class, [$commandId]);
            }

            if (in_array($command->status, ['completed', 'acknowledged'], true)) {
                return $command;
            }

            if ($command->status !== 'sent') {
                Log::warning('Ignoring ZKTeco command acknowledgement for non-sent command', [
                    'serial_number' => $device->serial_number,
                    'command_id' => $command->id,
                    'status' => $command->status,
                    'return_code' => $returnCode,
                ]);

                return $command;
            }

            $status = $this->resolveStatus($returnCode);

            $command->update([
                'status' => $status,
                'return_code' => $returnCode,
                'acknowledged_at' => now(),
            ]);

            Log::info('ZKTeco command acknowledged', [
                'serial_number' => $device->serial_number,
                'command_id' => $command->id,
                'return_code' => $returnCode,
                'status' => $status,
            ]);

            return $command->fresh();
        });
    }

    public function markAsFailed(ZktecoCommand $command, ?int $returnCode = null): ZktecoCommand
    {
        $command->update([
            'status' => 'failed',
            'return_code' => $returnCode,
            'acknowledged_at' => now(),
        ]);

        Log::info('ZKTeco command failed', [
            'serial_number' => $command->serial_number,
            'command_id' => $command->id,
            'return_code' => $returnCode,
        ]);

        return $command->fresh();
    }

    public function resolveStatus(int $returnCode): string
    {
        // ZKTeco Push SDK convention: Return=0 means success.
        // Verify against your device firmware if non-zero codes differ.
        return $returnCode === 0 ? 'completed' : 'failed';
    }
}
