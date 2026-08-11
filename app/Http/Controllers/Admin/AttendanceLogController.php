<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAttendanceLogRequest;
use App\Models\AttendanceLog;
use App\Models\Member;
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
                    $builder->where('user_id', 'like', "%{$search}%")
                        ->orWhere('sn', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['sn'] ?? null), fn ($query) => $query->where('sn', $filters['sn']))
            ->when(filled($filters['from_date'] ?? null), fn ($query) => $query->whereDate('timestamp', '>=', $filters['from_date']))
            ->when(filled($filters['to_date'] ?? null), fn ($query) => $query->whereDate('timestamp', '<=', $filters['to_date']))
            ->latest('timestamp')
            ->paginate(config('gym.pagination.per_page'))
            ->withQueryString();

        $memberCodes = $logs->getCollection()->pluck('user_id')->unique()->filter()->values();
        $serialNumbers = $logs->getCollection()->pluck('sn')->unique()->filter()->values();

        $members = Member::query()
            ->whereIn('member_code', $memberCodes)
            ->get(['id', 'name', 'member_code'])
            ->keyBy('member_code');

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
