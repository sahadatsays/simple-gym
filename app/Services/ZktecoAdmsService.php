<?php

namespace App\Services;

use App\Models\ZktecoDevice;
use App\Support\ZktecoAdmsResponseBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoAdmsService
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private ZktecoDeviceCommandService $commands,
    ) {}

    public function handleGetRequest(Request $request): string
    {
        $serialNumber = $this->devices->resolveSerialNumber($request);

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

        $this->devices->touchDevice($device);

        if ($this->hasCommandAcknowledgement($request)) {
            $this->handleCommandAcknowledgement($request);
        }

        $command = $this->commands->getNextPendingCommand($device);

        if ($command === null) {
            return ZktecoAdmsResponseBuilder::ok();
        }

        return ZktecoAdmsResponseBuilder::command($command->id, $command->command);
    }

    public function handlePush(Request $request): string
    {
        $serialNumber = $this->devices->resolveSerialNumber($request);

        if ($serialNumber === null) {
            Log::warning('ZKTeco push request missing serial number', [
                'method' => $request->method(),
                'query' => $request->query(),
            ]);

            return ZktecoAdmsResponseBuilder::push();
        }

        $device = ZktecoDevice::query()->firstOrCreate(
            ['serial_number' => $serialNumber],
            ['status' => 'pending'],
        );

        $this->devices->touchDevice($device);

        if ($request->isMethod('post') && ! $request->filled('table')) {
            $this->devices->logUnexpectedPushRequest($request);
        }

        return ZktecoAdmsResponseBuilder::push();
    }

    public function handleDeviceCmd(Request $request): void
    {
        $rawContent = trim($request->getContent());

        Log::info('ZKTeco devicecmd received', [
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $rawContent,
            'query' => $request->query(),
        ]);

        $parsedData = [];

        if ($rawContent !== '') {
            parse_str($rawContent, $parsedData);
        }

        $serialNumber = $this->devices->resolveSerialNumber($request)
            ?? $this->stringValue($parsedData['SN'] ?? $parsedData['sn'] ?? null);

        $commandId = $this->intValue($parsedData['ID'] ?? $parsedData['id'] ?? null)
            ?? $this->parseCommandId($request);

        $returnCode = $this->intValue($parsedData['Return'] ?? $parsedData['return'] ?? null)
            ?? $this->parseReturnCode($request);

        if ($serialNumber === null || $commandId === null || $returnCode === null) {
            Log::error('Invalid ZKTeco devicecmd payload', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
                'return_code' => $returnCode,
                'raw_content' => $rawContent,
                'parsed_data' => $parsedData,
            ]);

            return;
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', $serialNumber)
            ->first();

        if ($device === null) {
            Log::error('ZKTeco devicecmd received for unknown device', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
                'return_code' => $returnCode,
            ]);

            return;
        }

        $this->devices->touchDevice($device);

        try {
            $command = $this->commands->acknowledge($device, $commandId, $returnCode);

            Log::info('ZKTeco devicecmd processed', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
                'return_code' => $returnCode,
                'status' => $command->status,
            ]);
        } catch (ModelNotFoundException) {
            Log::error('ZKTeco devicecmd command not found', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
                'return_code' => $returnCode,
            ]);
        }
    }

    private function handleCommandAcknowledgement(Request $request): void
    {
        $serialNumber = $this->devices->resolveSerialNumber($request);
        $commandId = $this->parseCommandId($request);
        $returnCode = $this->parseReturnCode($request);

        if ($serialNumber === null || $commandId === null || $returnCode === null) {
            Log::warning('Invalid ZKTeco command response', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
                'return_code' => $returnCode,
            ]);

            return;
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', $serialNumber)
            ->first();

        if ($device === null) {
            Log::warning('Invalid ZKTeco command response for unknown device', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
            ]);

            return;
        }

        $this->devices->touchDevice($device);

        try {
            $this->commands->acknowledge($device, $commandId, $returnCode);
        } catch (ModelNotFoundException) {
            Log::warning('Invalid ZKTeco command response', [
                'serial_number' => $serialNumber,
                'command_id' => $commandId,
            ]);
        }
    }

    private function hasCommandAcknowledgement(Request $request): bool
    {
        return $this->parseCommandId($request) !== null
            && $this->parseReturnCode($request) !== null;
    }

    private function parseCommandId(Request $request): ?int
    {
        foreach (['ID', 'id', 'CmdID', 'cmdid'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function parseReturnCode(Request $request): ?int
    {
        foreach (['Return', 'return', 'Ret', 'ret'] as $key) {
            $value = $request->query($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function intValue(mixed $value): ?int
    {
        if (! is_scalar($value) || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
