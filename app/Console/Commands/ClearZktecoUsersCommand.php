<?php

namespace App\Console\Commands;

use App\Models\ZktecoDevice;
use App\Services\ZktecoUserClearService;
use Illuminate\Console\Command;

class ClearZktecoUsersCommand extends Command
{
    protected $signature = 'zkteco:clear-users
                            {serial? : Device serial number}
                            {--all : Clear card users on every registered device}
                            {--local-only : Clear server-side removal records without queuing device commands}';

    protected $description = 'Remove all RFID card users from ZKTeco devices';

    public function handle(ZktecoUserClearService $userClearService): int
    {
        $queueDeviceCommand = ! $this->option('local-only');

        if ($this->option('all')) {
            $results = $userClearService->clearAllDevices($queueDeviceCommand);

            if ($results->isEmpty()) {
                $this->warn('No ZKTeco devices are registered.');

                return self::SUCCESS;
            }

            $this->table(
                ['Serial Number', 'Assigned Cards', 'Delete Commands', 'CLEAR USER Queued'],
                $results->map(fn (array $result): array => [
                    $result['device']->serial_number,
                    $result['assigned_cards'],
                    $result['delete_commands_queued'],
                    $result['clear_user_command'] !== null ? 'Yes' : 'No',
                ])->all(),
            );

            $this->info('ZKTeco card user clearing completed for all devices.');

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

        $result = $userClearService->clearAllUsersWithCards($device, $queueDeviceCommand);

        $this->info("Queued card user removal for device [{$device->serial_number}].");
        $this->line("Assigned cards: {$result['assigned_cards']}");
        $this->line("Delete commands queued: {$result['delete_commands_queued']}.");

        if ($result['clear_user_command'] !== null) {
            $this->line('Queued CLEAR USER command for the device.');
        } else {
            $this->line('Skipped device commands because --local-only was used.');
        }

        return self::SUCCESS;
    }
}
