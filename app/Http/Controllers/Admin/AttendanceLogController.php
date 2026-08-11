<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAttendanceLogRequest;
use App\Models\AttendanceLog;
use App\Models\RfidCard;
use App\Models\ZktecoDevice;
use Illuminate\View\View;

class AttendanceLogController extends Controller
{
    public function index(IndexAttendanceLogRequest $request): View
    {
        $filters = $request->validated();

        $logs = AttendanceLog::query()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($builder) use ($search): void {
                    $builder->where('pim', 'like', "%{$search}%")
                        ->orWhere('sn', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['sn'] ?? null), fn ($query) => $query->where('sn', $filters['sn']))
            ->when(filled($filters['from_date'] ?? null), fn ($query) => $query->whereDate('timestamp', '>=', $filters['from_date']))
            ->when(filled($filters['to_date'] ?? null), fn ($query) => $query->whereDate('timestamp', '<=', $filters['to_date']))
            ->latest('timestamp')
            ->paginate(config('gym.pagination.per_page'))
            ->withQueryString();

        $members = RfidCard::membersKeyedByPim(
            $logs->getCollection()->pluck('pim'),
        );

        $serialNumbers = $logs->getCollection()->pluck('sn')->unique()->filter()->values();

        $devices = ZktecoDevice::query()
            ->whereIn('serial_number', $serialNumbers)
            ->get(['id', 'serial_number'])
            ->keyBy('serial_number');

        $deviceOptions = ZktecoDevice::query()
            ->orderBy('serial_number')
            ->pluck('serial_number', 'serial_number');

        return view('admin.attendance-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'members' => $members,
            'devices' => $devices,
            'deviceOptions' => $deviceOptions,
        ]);
    }
}
