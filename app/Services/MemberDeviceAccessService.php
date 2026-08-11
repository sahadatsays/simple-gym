<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Enums\MemberStatus;
use App\Enums\ZktecoDeviceStatus;
use App\Models\Member;
use App\Models\MemberZktecoAccessRemoval;
use App\Models\RfidCard;
use App\Models\ZktecoCommand;
use App\Models\ZktecoDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MemberDeviceAccessService extends BaseService
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private RfidCardRepositoryInterface $rfidCards,
        private MemberRepositoryInterface $members,
        private ZktecoCommandBuilder $commandBuilder,
    ) {}

    public function grantMemberAccess(int $memberId): bool
    {
        $member = Member::query()
            ->with('activeRfidCard')
            ->find($memberId);

        if ($member === null) {
            Log::warning('Skipping ZKTeco access grant for missing member', [
                'member_id' => $memberId,
            ]);

            return false;
        }

        $userPin = trim($member->member_code);
        $card = $member->activeRfidCard;

        if ($userPin === '' || $card === null) {
            Log::info('Skipping ZKTeco access grant without member code or active card', [
                'member_id' => $member->id,
            ]);

            return false;
        }

        if (! $member->isActive()) {
            Log::info('Skipping ZKTeco access grant for inactive member', [
                'member_id' => $member->id,
                'member_code' => $userPin,
                'status' => $member->status->value,
            ]);

            return false;
        }

        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        if ($activeDevices->isEmpty()) {
            Log::info('No active ZKTeco devices available for access grant', [
                'member_id' => $member->id,
                'member_code' => $userPin,
            ]);

            return false;
        }

        $userData = $this->buildUserPayload($member, $card);
        $queuedAnyCommand = false;

        $this->transaction(function () use ($member, $userPin, $activeDevices, $userData, &$queuedAnyCommand): void {
            MemberZktecoAccessRemoval::query()
                ->where('member_id', $member->id)
                ->delete();

            foreach ($activeDevices as $device) {
                if ($this->hasPendingUpsertCommand($device, $userData)) {
                    continue;
                }

                $command = $this->devices->upsertUser($device, $userData);

                $queuedAnyCommand = true;

                Log::info('Queued ZKTeco user sync for member', [
                    'member_id' => $member->id,
                    'member_code' => $userPin,
                    'card_number' => $userData['card_number'],
                    'serial_number' => $device->serial_number,
                    'command_id' => $command->id,
                ]);
            }
        });

        return $queuedAnyCommand;
    }

    /**
     * @return array{
     *     user_id: string,
     *     name: string,
     *     card_number: string,
     *     privilege: int,
     *     group: int
     * }
     */
    private function buildUserPayload(Member $member, RfidCard $card): array
    {
        return [
            'user_id' => $member->member_code,
            'name' => $member->name,
            'card_number' => $card->card_number,
            'privilege' => 0,
            'group' => 1,
        ];
    }

    /**
     * @param  array{
     *     user_id: string,
     *     name: string,
     *     card_number: string,
     *     privilege: int,
     *     group: int
     * }  $userData
     */
    private function hasPendingUpsertCommand(ZktecoDevice $device, array $userData): bool
    {
        $upsertCommand = $this->commandBuilder->upsertUser($userData);

        return ZktecoCommand::query()
            ->where('serial_number', $device->serial_number)
            ->where('command', $upsertCommand)
            ->whereIn('status', ['pending', 'sent'])
            ->exists();
    }

    /**
     * @return int Number of members processed.
     */
    public function revokeExpiredMemberAccess(): int
    {
        $processed = 0;

        Member::query()
            ->expired()
            ->with('activeRfidCard')
            ->orderBy('id')
            ->chunkById(100, function (Collection $members) use (&$processed): void {
                foreach ($members as $member) {
                    if ($this->revokeMemberAccess($member)) {
                        $processed++;
                    }
                }
            });

        return $processed;
    }

    public function revokeMemberDeviceAccess(int $memberId): bool
    {
        $member = Member::query()->find($memberId);

        if ($member === null) {
            Log::warning('Skipping ZKTeco access removal for missing member', [
                'member_id' => $memberId,
            ]);

            return false;
        }

        return $this->queueDeviceAccessRemoval($member);
    }

    public function revokeMemberAccess(Member $member): bool
    {
        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        $queuedAnyCommand = $this->queueDeviceAccessRemoval($member);

        $this->transaction(function () use ($member): void {
            if ($member->activeRfidCard !== null) {
                $this->disableAllCardsForMember($member);
            }

            $this->markMemberExpired($member);
        });

        return $queuedAnyCommand || $this->hasRevokedAccessOnAllDevices($member, $activeDevices);
    }

    private function queueDeviceAccessRemoval(Member $member): bool
    {
        $userPin = trim($member->member_code);

        if ($userPin === '') {
            Log::warning('Skipping ZKTeco access removal for member without PIN', [
                'member_id' => $member->id,
            ]);

            return false;
        }

        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        if ($activeDevices->isEmpty()) {
            Log::info('No active ZKTeco devices available for access removal', [
                'member_id' => $member->id,
                'member_code' => $userPin,
            ]);

            return false;
        }

        $queuedAnyCommand = false;

        $this->transaction(function () use ($member, $userPin, $activeDevices, &$queuedAnyCommand): void {
            foreach ($activeDevices as $device) {
                if ($this->hasRevokedAccess($member, $device)) {
                    continue;
                }

                if ($this->hasPendingRemovalCommand($device, $userPin)) {
                    MemberZktecoAccessRemoval::query()->firstOrCreate(
                        [
                            'member_id' => $member->id,
                            'serial_number' => $device->serial_number,
                        ],
                        [
                            'zkteco_command_id' => null,
                            'revoked_at' => now(),
                        ],
                    );

                    continue;
                }

                $command = $this->devices->deleteUser($device, $userPin);

                MemberZktecoAccessRemoval::query()->create([
                    'member_id' => $member->id,
                    'serial_number' => $device->serial_number,
                    'zkteco_command_id' => $command->id,
                    'revoked_at' => now(),
                ]);

                $queuedAnyCommand = true;

                Log::info('Queued ZKTeco user removal for member', [
                    'member_id' => $member->id,
                    'member_code' => $userPin,
                    'serial_number' => $device->serial_number,
                    'command_id' => $command->id,
                ]);
            }
        });

        return $queuedAnyCommand || $this->hasRevokedAccessOnAllDevices($member, $activeDevices);
    }

    private function hasRevokedAccess(Member $member, ZktecoDevice $device): bool
    {
        return MemberZktecoAccessRemoval::query()
            ->where('member_id', $member->id)
            ->where('serial_number', $device->serial_number)
            ->exists();
    }

    /**
     * @param  Collection<int, ZktecoDevice>  $devices
     */
    private function hasRevokedAccessOnAllDevices(Member $member, Collection $devices): bool
    {
        if ($devices->isEmpty()) {
            return false;
        }

        $removedDeviceCount = MemberZktecoAccessRemoval::query()
            ->where('member_id', $member->id)
            ->whereIn('serial_number', $devices->pluck('serial_number'))
            ->count();

        return $removedDeviceCount >= $devices->count();
    }

    private function hasPendingRemovalCommand(ZktecoDevice $device, string $userPin): bool
    {
        $deleteCommand = 'DATA DELETE user Pin='.$userPin;

        return ZktecoCommand::query()
            ->where('serial_number', $device->serial_number)
            ->where('command', $deleteCommand)
            ->whereIn('status', ['pending', 'sent'])
            ->exists();
    }

    private function disableAllCardsForMember(Member $member): void
    {
        $this->rfidCards->disableActiveCardsForMember($member);

        $this->members->update($member, [
            'rfid_card' => null,
        ]);
    }

    private function markMemberExpired(Member $member): void
    {
        if ($member->status !== MemberStatus::Active) {
            return;
        }

        if ($member->membership_expires_at === null || $member->membership_expires_at->gte(today())) {
            return;
        }

        $member->forceFill([
            'status' => MemberStatus::Expired,
        ])->save();
    }
}
