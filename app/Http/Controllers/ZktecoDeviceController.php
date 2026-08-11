<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreZktecoDeviceUserRequest;
use App\Models\ZktecoDevice;
use App\Services\ZktecoDeviceService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ZktecoDeviceController extends Controller
{
    public function __construct(
        private ZktecoDeviceService $devices,
    ) {}

    public function index(): JsonResponse
    {
        $devices = ZktecoDevice::query()
            ->withCount('commands')
            ->orderByDesc('last_seen_at')
            ->orderBy('serial_number')
            ->get();

        return response()->json([
            'data' => $devices,
        ]);
    }

    public function show(ZktecoDevice $device): JsonResponse
    {
        $device->load([
            'commands' => fn ($query) => $query->latest()->limit(25),
        ]);

        return response()->json([
            'data' => $device,
        ]);
    }

    public function reboot(ZktecoDevice $device): JsonResponse
    {
        $command = $this->devices->reboot($device);

        return response()->json([
            'message' => 'Reboot command queued.',
            'data' => $command,
        ], 201);
    }

    public function restart(ZktecoDevice $device): JsonResponse
    {
        $command = $this->devices->restart($device);

        return response()->json([
            'message' => 'Restart command queued.',
            'data' => $command,
        ], 201);
    }

    public function storeUser(StoreZktecoDeviceUserRequest $request, ZktecoDevice $device): JsonResponse
    {
        try {
            $command = $this->devices->upsertUser($device, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'User upsert command queued.',
            'data' => $command,
        ], 201);
    }

    public function deleteUser(ZktecoDevice $device, string $userId): JsonResponse
    {
        try {
            $command = $this->devices->deleteUser($device, $userId);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Delete user command queued.',
            'data' => $command,
        ], 201);
    }
}
