<?php

namespace App\Services;

use App\Models\MemberZktecoAccessRemoval;
use App\Models\RfidCard;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ZktecoUserClearService extends BaseService
{
    public function __construct(
        private ZktecoCommandBuilder $commandBuilder,
        private ZktecoDeviceCommandService $commands,
    ) {}

    /**
     * @return array{
     *     assigned_cards: int,
     *     delete_commands_queued: int,
     *     clear_user_command: ?ZktecoCommand
     * }
     */
    public function clearAllUsersWithCards(ZktecoDevice $device, bool $queueDeviceCommand = true): array
    {
        return $this->transaction(function () use ($device, $queueDeviceCommand): array {
            $assignedCards = RfidCard::query()
                ->whereNotNull('member_id')
                ->orderBy('id')
                ->get(['id']);

            MemberZktecoAccessRemoval::query()
                ->where('serial_number', $device->serial_number)
                ->delete();

            $deleteCommandsQueued = 0;

            if ($queueDeviceCommand) {
                foreach ($assignedCards as $card) {
                    $pim = (string) $card->id;

                    if ($this->hasPendingDeleteCommand($device, $pim)) {
                        continue;
                    }

                    $this->commands->queue($device, $this->commandBuilder->deleteUser($pim));
                    $deleteCommandsQueued++;
                }

                $clearUserCommand = $this->commands->queue($device, $this->commandBuilder->clearUsers());
            } else {
                $clearUserCommand = null;
            }

            Log::info('ZKTeco card users cleared', [
                'serial_number' => $device->serial_number,
                'assigned_cards' => $assignedCards->count(),
                'delete_commands_queued' => $deleteCommandsQueued,
                'clear_user_command_queued' => $clearUserCommand !== null,
            ]);

            return [
                'assigned_cards' => $assignedCards->count(),
                'delete_commands_queued' => $deleteCommandsQueued,
                'clear_user_command' => $clearUserCommand,
            ];
        });
    }

    /**
     * @return Collection<int, array{
     *     device: ZktecoDevice,
     *     assigned_cards: int,
     *     delete_commands_queued: int,
     *     clear_user_command: ?ZktecoCommand
     * }>
     */
    public function clearAllDevices(bool $queueDeviceCommand = true): Collection
    {
        return ZktecoDevice::query()
            ->orderBy('serial_number')
            ->get()
            ->map(fn (ZktecoDevice $device): array => [
                'device' => $device,
                ...$this->clearAllUsersWithCards($device, $queueDeviceCommand),
            ]);
    }

    private function hasPendingDeleteCommand(ZktecoDevice $device, string $pim): bool
    {
        return ZktecoCommand::query()
            ->where('serial_number', $device->serial_number)
            ->where('command', $this->commandBuilder->deleteUser($pim))
            ->whereIn('status', ['pending', 'sent'])
            ->exists();
    }
}
