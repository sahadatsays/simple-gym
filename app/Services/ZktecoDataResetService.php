<?php

namespace App\Services;

use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZktecoDataResetService extends BaseService
{
    public function __construct(
        private ZktecoCommandBuilder $commandBuilder,
        private ZktecoDeviceCommandService $commands,
    ) {}

    /**
     * @return array{
     *     attendance_logs_deleted: int,
     *     attendance_failures_deleted: int,
     *     command: ?ZktecoCommand
     * }
     */
    public function resetDevice(ZktecoDevice $device, bool $queueDeviceCommand = true): array
    {
        return $this->transaction(function () use ($device, $queueDeviceCommand): array {
            $serialNumber = $device->serial_number;

            $attendanceLogsDeleted = DB::table('attendance_logs')
                ->where('sn', $serialNumber)
                ->delete();

            $attendanceFailuresDeleted = DB::table('attendance_log_failures')
                ->where('sn', $serialNumber)
                ->delete();

            $this->resetAttendanceStamp($device);

            $command = $queueDeviceCommand
                ? $this->commands->queue($device, $this->commandBuilder->clearLog())
                : null;

            Log::info('ZKTeco attendance data reset', [
                'serial_number' => $serialNumber,
                'attendance_logs_deleted' => $attendanceLogsDeleted,
                'attendance_failures_deleted' => $attendanceFailuresDeleted,
                'device_command_queued' => $command !== null,
            ]);

            return [
                'attendance_logs_deleted' => $attendanceLogsDeleted,
                'attendance_failures_deleted' => $attendanceFailuresDeleted,
                'command' => $command,
            ];
        });
    }

    /**
     * @return Collection<int, array{
     *     device: ZktecoDevice,
     *     attendance_logs_deleted: int,
     *     attendance_failures_deleted: int,
     *     command: ?ZktecoCommand
     * }>
     */
    public function resetAllDevices(bool $queueDeviceCommand = true): Collection
    {
        return ZktecoDevice::query()
            ->orderBy('serial_number')
            ->get()
            ->map(fn (ZktecoDevice $device): array => [
                'device' => $device,
                ...$this->resetDevice($device, $queueDeviceCommand),
            ]);
    }

    private function resetAttendanceStamp(ZktecoDevice $device): void
    {
        $stamps = $device->stamps ?? [];
        unset($stamps['ATTLOG']);

        $device->forceFill([
            'stamps' => $stamps === [] ? null : $stamps,
        ])->save();
    }
}
