<?php

namespace App\Services;

use App\Contracts\Zkteco\ZktecoClientInterface;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Support\ZktecoAdmsResponseBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoDeviceService extends BaseService
{
    public function __construct(
        private ZktecoClientInterface $client,
    ) {}

    public function resolveSerialNumber(Request $request): ?string
    {
        foreach (['SN', 'sn'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    public function registerDevice(Request $request): ?ZktecoDevice
    {
        $serialNumber = $this->resolveSerialNumber($request);

        if ($serialNumber === null) {
            Log::warning('ZKTeco registry request missing serial number', [
                'query' => $request->query(),
                'method' => $request->method(),
            ]);

            return null;
        }

        $device = ZktecoDevice::query()->firstOrNew([
            'serial_number' => $serialNumber,
        ]);

        $wasRecentlyCreated = ! $device->exists;

        $device->last_seen_at = now();
        $device->status = 'active';

        if ($protocolGeneration = $this->resolveProtocolGeneration($request)) {
            $device->protocol_generation = $protocolGeneration;
        }

        if ($capabilities = $this->resolveCapabilities($request)) {
            $device->capabilities = $capabilities;
        }

        $device->save();

        Log::info($wasRecentlyCreated ? 'ZKTeco device registered' : 'ZKTeco device registry refreshed', [
            'serial_number' => $device->serial_number,
            'protocol_generation' => $device->protocol_generation,
        ]);

        return $device;
    }

    public function touchDevice(ZktecoDevice $device): ZktecoDevice
    {
        $device->forceFill([
            'last_seen_at' => now(),
            'status' => 'active',
        ])->save();

        return $device;
    }

    public function registerFromCdata(Request $request): ?ZktecoDevice
    {
        $serialNumber = $this->resolveSerialNumber($request);

        if ($serialNumber === null) {
            Log::warning('ZKTeco cdata request missing serial number', [
                'query' => $request->query(),
                'method' => $request->method(),
            ]);

            return null;
        }

        $device = ZktecoDevice::query()->firstOrNew([
            'serial_number' => $serialNumber,
        ]);

        $wasRecentlyCreated = ! $device->exists;

        $device->last_seen_at = now();

        if ($wasRecentlyCreated) {
            $device->status = 'pending';
            $device->protocol_generation = $this->resolveProtocolGeneration($request) ?? 'Legacy';
        }

        if ($protocolGeneration = $this->resolveProtocolGeneration($request)) {
            $device->protocol_generation = $protocolGeneration;
        }

        if ($capabilities = $this->resolveCapabilities($request)) {
            $device->capabilities = $capabilities;
        }

        $device->save();

        Log::info($wasRecentlyCreated ? 'ZKTeco device discovered via cdata' : 'ZKTeco device cdata refreshed', [
            'serial_number' => $device->serial_number,
            'protocol_generation' => $device->protocol_generation,
            'status' => $device->status,
        ]);

        return $device;
    }

    public function markSeen(ZktecoDevice $device): ZktecoDevice
    {
        $device->forceFill([
            'last_seen_at' => now(),
        ])->save();

        return $device;
    }

    public function updateStamp(ZktecoDevice $device, string $table, string $stamp): ZktecoDevice
    {
        $stamps = $device->stamps ?? [];
        $stamps[strtoupper($table)] = $stamp;

        $device->forceFill([
            'stamps' => $stamps,
        ])->save();

        return $device;
    }

    public function approve(ZktecoDevice $device): ZktecoDevice
    {
        $device->forceFill([
            'status' => 'active',
        ])->save();

        Log::info('ZKTeco device approved', [
            'serial_number' => $device->serial_number,
        ]);

        return $device;
    }

    public function suspend(ZktecoDevice $device): ZktecoDevice
    {
        $device->forceFill([
            'status' => 'suspended',
        ])->save();

        Log::info('ZKTeco device suspended', [
            'serial_number' => $device->serial_number,
        ]);

        return $device;
    }

    public function queueCommand(ZktecoDevice $device, string $command): ZktecoCommand
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

    public function reboot(ZktecoDevice $device): ZktecoCommand
    {
        return $this->queueCommand(
            $device,
            $this->client->reboot($device->serial_number),
        );
    }

    public function restart(ZktecoDevice $device): ZktecoCommand
    {
        return $this->queueCommand(
            $device,
            $this->client->restart($device->serial_number),
        );
    }

    public function deleteUser(ZktecoDevice $device, string $userId): ZktecoCommand
    {
        return $this->queueCommand(
            $device,
            $this->client->deleteUser($device->serial_number, $userId),
        );
    }

    /**
     * @param  array{
     *     uid?: int|null,
     *     user_id: string,
     *     name?: string|null,
     *     privilege?: int|null,
     *     card_number?: string|null
     * }  $userData
     */
    public function upsertUser(ZktecoDevice $device, array $userData): ZktecoCommand
    {
        return $this->queueCommand(
            $device,
            $this->client->upsertUser($device->serial_number, $userData),
        );
    }

    public function pullNextPendingCommand(ZktecoDevice $device): ?ZktecoCommand
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

            $command->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('ZKTeco command sent to device', [
                'serial_number' => $device->serial_number,
                'command_id' => $command->id,
            ]);

            return $command->fresh();
        });
    }

    public function getRequestResponse(Request $request): string
    {
        $serialNumber = $this->resolveSerialNumber($request);

        if ($serialNumber === null) {
            return ZktecoAdmsResponseBuilder::ok();
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', $serialNumber)
            ->first();

        if ($device === null) {
            Log::warning('Unknown ZKTeco device requested command', [
                'serial_number' => $serialNumber,
            ]);

            return ZktecoAdmsResponseBuilder::ok();
        }

        $this->touchDevice($device);

        $command = $this->pullNextPendingCommand($device);

        if ($command === null) {
            return ZktecoAdmsResponseBuilder::ok();
        }

        return $command->command;
    }

    public function acknowledgeCommandFromRequest(Request $request): void
    {
        $commandId = $this->resolveCommandId($request);
        $returnCode = $this->resolveReturnCode($request);

        if ($commandId === null || $returnCode === null) {
            return;
        }

        $command = ZktecoCommand::query()->find($commandId);

        if ($command === null) {
            Log::warning('ZKTeco command acknowledgment for unknown command', [
                'command_id' => $commandId,
                'return_code' => $returnCode,
            ]);

            return;
        }

        $status = $returnCode === 0 ? 'acknowledged' : 'failed';

        $command->update([
            'return_code' => $returnCode,
            'status' => $status,
            'acknowledged_at' => now(),
        ]);

        Log::info('ZKTeco command acknowledged', [
            'serial_number' => $command->serial_number,
            'command_id' => $command->id,
            'return_code' => $returnCode,
            'status' => $status,
        ]);
    }

    public function logUnexpectedPushRequest(Request $request): void
    {
        Log::warning('Unexpected ZKTeco push request format', [
            'method' => $request->method(),
            'query' => $request->query(),
            'content_type' => $request->header('Content-Type'),
        ]);
    }

    private function resolveProtocolGeneration(Request $request): ?string
    {
        $pushVersion = $request->query('pushver') ?? $request->input('pushver');

        if (is_string($pushVersion) && $pushVersion !== '') {
            return 'Push '.$pushVersion;
        }

        $options = $request->query('options') ?? $request->input('options');

        if (is_string($options) && str_contains(strtolower($options), 'legacy')) {
            return 'Legacy';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCapabilities(Request $request): ?array
    {
        $capabilities = [];

        foreach (['pushver', 'options', 'PushOptionsFlag', 'language'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if ($value !== null && $value !== '') {
                $capabilities[$key] = $value;
            }
        }

        return $capabilities === [] ? null : $capabilities;
    }

    private function resolveCommandId(Request $request): ?int
    {
        foreach (['ID', 'id', 'CmdID', 'cmdid'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function resolveReturnCode(Request $request): ?int
    {
        foreach (['Return', 'return', 'Ret', 'ret'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}
