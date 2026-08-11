<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ZktecoDeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteZktecoDeviceUserRequest;
use App\Http\Requests\Admin\IndexZktecoDeviceRequest;
use App\Http\Requests\Admin\StoreZktecoDeviceUserRequest;
use App\Models\AttendanceLog;
use App\Models\ZktecoDevice;
use App\Services\ZktecoDeviceService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ZktecoDeviceController extends Controller
{
    public function __construct(
        private ZktecoDeviceService $devices,
    ) {}

    public function index(IndexZktecoDeviceRequest $request): View
    {
        $filters = $request->validated();

        $devices = ZktecoDevice::query()
            ->withCount('commands')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($builder) use ($search): void {
                    $builder->where('serial_number', 'like', "%{$search}%")
                        ->orWhere('protocol_generation', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('last_seen_at')
            ->orderBy('serial_number')
            ->paginate(config('gym.pagination.per_page'))
            ->withQueryString();

        return view('admin.zkteco-devices.index', [
            'devices' => $devices,
            'filters' => $filters,
            'statuses' => ZktecoDeviceStatus::cases(),
        ]);
    }

    public function show(ZktecoDevice $device): View
    {
        $this->authorize('view', $device);

        $device->load([
            'commands' => fn ($query) => $query->latest()->limit(25),
        ]);

        $recentAttendance = AttendanceLog::query()
            ->where('sn', $device->serial_number)
            ->latest('timestamp')
            ->limit(15)
            ->get();

        return view('admin.zkteco-devices.show', [
            'device' => $device,
            'recentAttendance' => $recentAttendance,
        ]);
    }

    public function approve(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $this->devices->approve($device);

        Flash::success('Device approved. It can now sync attendance data.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function suspend(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $this->devices->suspend($device);

        Flash::success('Device suspended. Data uploads are blocked until approved again.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function reboot(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $this->devices->reboot($device);

        Flash::success('Reboot command queued. The device will receive it on its next push poll.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function restart(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $this->devices->restart($device);

        Flash::success('Restart command queued. The device will receive it on its next push poll.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function storeUser(StoreZktecoDeviceUserRequest $request, ZktecoDevice $device): RedirectResponse
    {
        try {
            $this->devices->upsertUser($device, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back()->withInput();
        }

        Flash::success('User sync command queued for the device.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function deleteUser(ZktecoDevice $device, string $userId): RedirectResponse
    {
        $this->authorize('manage', $device);

        try {
            $this->devices->deleteUser($device, $userId);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Delete user command queued for the device.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function destroyUser(DeleteZktecoDeviceUserRequest $request, ZktecoDevice $device): RedirectResponse
    {
        return $this->deleteUser($device, $request->validated('user_id'));
    }
}
