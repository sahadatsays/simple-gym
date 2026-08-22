<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ZktecoDeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteZktecoDeviceUserRequest;
use App\Http\Requests\Admin\IndexZktecoDeviceRequest;
use App\Http\Requests\Admin\StoreZktecoDeviceUserRequest;
use App\Models\RfidCard;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use App\Services\MemberDeviceAccessService;
use App\Services\ZktecoDataResetService;
use App\Services\ZktecoDeviceService;
use App\Services\ZktecoUserClearService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class ZktecoDeviceController extends Controller
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private ZktecoDataResetService $dataReset,
        private ZktecoUserClearService $userClear,
        private MemberDeviceAccessService $memberDeviceAccess,
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

    public function show(Request $request, ZktecoDevice $device): View
    {
        $this->authorize('view', $device);

        $perPage = config('gym.pagination.per_page');

        $commands = ZktecoCommand::query()
            ->where('serial_number', $device->serial_number)
            ->latest('id')
            ->paginate($perPage, ['*'], 'commands_page')
            ->withQueryString();

        $successQuery = DB::table('attendance_logs')
            ->select(
                'id',
                'sn',
                'pim',
                'timestamp',
                'punch_status',
                'verify_mode',
                DB::raw('NULL as card_number'),
                DB::raw("'success' as verification_status"),
            )
            ->where('sn', $device->serial_number);

        $failureQuery = DB::table('attendance_log_failures')
            ->select(
                'id',
                'sn',
                'pim',
                'timestamp',
                'punch_status',
                'verify_mode',
                'card_number',
                DB::raw("'failed' as verification_status"),
            )
            ->where('sn', $device->serial_number);

        $attendanceEvents = DB::query()
            ->fromSub($successQuery->unionAll($failureQuery), 'attendance_events')
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'attendance_page')
            ->withQueryString();

        $members = RfidCard::membersKeyedByPim(
            collect($attendanceEvents->items())->pluck('pim'),
        );

        return view('admin.zkteco-devices.show', [
            'device' => $device,
            'commands' => $commands,
            'attendanceEvents' => $attendanceEvents,
            'members' => $members,
            'rfidCards' => RfidCard::query()
                ->with('member')
                ->whereNotNull('member_id')
                ->orderBy('card_number')
                ->get(['id', 'card_number', 'member_id', 'status']),
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

        Flash::success(__('settings.zkteco.reboot_queued'));

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function resetData(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $result = $this->dataReset->resetDevice($device);

        Flash::success(__('settings.zkteco.data_reset', [
            'logs' => $result['attendance_logs_deleted'],
            'failures' => $result['attendance_failures_deleted'],
        ]));

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function clearUsers(ZktecoDevice $device): RedirectResponse
    {
        $this->authorize('manage', $device);

        $result = $this->userClear->clearAllUsersWithCards($device);

        Flash::success(__('settings.zkteco.users_cleared', [
            'cards' => $result['assigned_cards'],
            'commands' => $result['delete_commands_queued'],
        ]));

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function storeUser(StoreZktecoDeviceUserRequest $request, ZktecoDevice $device): RedirectResponse
    {
        try {
            $userData = $this->memberDeviceAccess->resolveDeviceUserDataFromPim(
                (int) $request->validated('pim'),
            );

            if ($request->filled('privilege')) {
                $userData['privilege'] = (int) $request->validated('privilege');
            }

            $this->devices->upsertUser($device, $userData);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back()->withInput();
        }

        Flash::success('User sync command queued for the device.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function deleteUser(ZktecoDevice $device, string $pim): RedirectResponse
    {
        $this->authorize('manage', $device);

        try {
            $this->devices->deleteUser($device, $pim);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Delete user command queued for the device.');

        return redirect()->route('admin.zkteco-devices.show', $device);
    }

    public function destroyUser(DeleteZktecoDeviceUserRequest $request, ZktecoDevice $device): RedirectResponse
    {
        try {
            $pim = $this->memberDeviceAccess->resolvePim((int) $request->validated('pim'));

            return $this->deleteUser($device, $pim);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back()->withInput();
        }
    }
}
