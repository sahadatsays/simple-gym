<?php

namespace App\Console\Commands;

use App\Models\ZktecoDevice;
use App\Services\ZktecoDataResetService;
use Illuminate\Console\Command;

class ResetZktecoDataCommand extends Command
{
    protected $signature = 'zkteco:reset-data
                            {serial? : Device serial number to reset}
                            {--all : Reset attendance data for every registered device}
                            {--local-only : Clear server attendance data without queuing CLEAR LOG on devices}';

    protected $description = 'Reset ZKTeco attendance data locally and queue CLEAR LOG on devices';

    public function handle(ZktecoDataResetService $resetService): int
    {
        $queueDeviceCommand = ! $this->option('local-only');

        if ($this->option('all')) {
            $results = $resetService->resetAllDevices($queueDeviceCommand);

            if ($results->isEmpty()) {
                $this->warn('No ZKTeco devices are registered.');

                return self::SUCCESS;
            }

            $this->table(
                ['Serial Number', 'Logs Deleted', 'Failures Deleted', 'CLEAR LOG Queued'],
                $results->map(fn (array $result): array => [
                    $result['device']->serial_number,
                    $result['attendance_logs_deleted'],
                    $result['attendance_failures_deleted'],
                    $result['command'] !== null ? 'Yes' : 'No',
                ])->all(),
            );

            $this->info('ZKTeco attendance data reset completed for all devices.');

            return self::SUCCESS;
        }

        $serial = $this->argument('serial');

        if (! is_string($serial) || trim($serial) === '') {
            $this->error('Provide a device serial number or use --all.');

            return self::INVALID;
        }

        $device = ZktecoDevice::query()
            ->where('serial_number', trim($serial))
            ->first();

        if ($device === null) {
            $this->error("No ZKTeco device found with serial number [{$serial}].");

            return self::FAILURE;
        }

        $result = $resetService->resetDevice($device, $queueDeviceCommand);

        $this->info("Reset attendance data for device [{$device->serial_number}].");
        $this->line("Deleted {$result['attendance_logs_deleted']} attendance log(s).");
        $this->line("Deleted {$result['attendance_failures_deleted']} failed attendance record(s).");

        if ($result['command'] !== null) {
            $this->line('Queued CLEAR LOG command for the device.');
        } else {
            $this->line('Skipped queuing CLEAR LOG because --local-only was used.');
        }

        return self::SUCCESS;
    }
}
