<?php

namespace App\Services;

use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoDeviceService extends BaseService
{
    public function __construct(
        private ZktecoCommandBuilder $commandBuilder,
        private ZktecoDeviceCommandService $commands,
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

    public function reboot(ZktecoDevice $device): ZktecoCommand
    {
        return $this->commands->queue(
            $device,
            $this->commandBuilder->reboot(),
        );
    }

    public function restart(ZktecoDevice $device): ZktecoCommand
    {
        return $this->commands->queue(
            $device,
            $this->commandBuilder->restart(),
        );
    }

    public function deleteUser(ZktecoDevice $device, string $userId): ZktecoCommand
    {
        return $this->commands->queue(
            $device,
            $this->commandBuilder->deleteUser($userId),
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
        return $this->commands->queue(
            $device,
            $this->commandBuilder->upsertUser($userData),
        );
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
}
