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
use InvalidArgumentException;

class MemberDeviceAccessService extends BaseService
{
    public function __construct(
        private ZktecoDeviceService $devices,
        private RfidCardRepositoryInterface $rfidCards,
        private MemberRepositoryInterface $members,
        private ZktecoCommandBuilder $commandBuilder,
        private MemberDeviceAccessPolicy $accessPolicy,
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

        if ($card === null) {
            Log::info('Skipping ZKTeco access grant without active RFID card', [
                'member_id' => $member->id,
            ]);

            return false;
        }

        if ($userPin === '') {
            Log::info('Skipping ZKTeco access grant without member UID', [
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

        if (! $this->accessPolicy->canSyncToDevice($member)) {
            Log::info('Skipping ZKTeco access grant due to device access policy', [
                'member_id' => $member->id,
                'member_code' => $userPin,
                'restricted' => $this->accessPolicy->isMemberCurrentlyRestricted($member),
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
                    'uid' => $userPin,
                    'pim' => $userData['pim'],
                    'card_number' => $userData['card_number'],
                    'serial_number' => $device->serial_number,
                    'command_id' => $command->id,
                ]);
            }
        });

        return $queuedAnyCommand;
    }

    public function reconcileMemberDeviceAccess(int $memberId): bool
    {
        $member = Member::query()
            ->with('activeRfidCard')
            ->find($memberId);

        if ($member === null) {
            return false;
        }

        if ($this->accessPolicy->canSyncToDevice($member)) {
            return $this->grantMemberAccess($memberId);
        }

        if ($this->accessPolicy->shouldRevokeFromDevice($member)) {
            return $this->revokeMemberDeviceAccess($memberId);
        }

        return false;
    }

    public function isMemberRemovedFromAllActiveDevices(Member $member): bool
    {
        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        return $this->hasRevokedAccessOnAllDevices($member, $activeDevices);
    }

    /**
     * @return array{
     *     pim: string,
     *     name: string,
     *     card_number: string,
     *     privilege: int,
     *     group: int
     * }
     */
    public function resolveDeviceUserDataFromPim(int $rfidCardId): array
    {
        $card = RfidCard::query()
            ->with('member')
            ->find($rfidCardId);

        if ($card === null) {
            throw new InvalidArgumentException('The selected RFID card was not found.');
        }

        if ($card->member === null) {
            throw new InvalidArgumentException('The selected RFID card is not assigned to a member.');
        }

        if (! $this->accessPolicy->canSyncToDevice($card->member)) {
            throw new InvalidArgumentException('This member cannot be synced to devices while access restrictions are active.');
        }

        return $this->buildUserPayload($card->member, $card);
    }

    public function resolvePim(int $rfidCardId): string
    {
        $card = RfidCard::query()->find($rfidCardId);

        if ($card === null) {
            throw new InvalidArgumentException('The selected RFID card was not found.');
        }

        return (string) $card->id;
    }

    /**
     * @return array{
     *     pim: string,
     *     name: string,
     *     card_number: string,
     *     privilege: int,
     *     group: int
     * }
     */
    private function buildUserPayload(Member $member, RfidCard $card): array
    {
        return [
            'pim' => (string) $card->id,
            'name' => $member->name,
            'card_number' => $card->card_number,
            'privilege' => 0,
            'group' => 1,
        ];
    }

    /**
     * @param  array{
     *     pim: string,
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

    public function revokeMemberDeviceAccess(int $memberId, ?int $rfidCardId = null): bool
    {
        $member = Member::query()
            ->with('activeRfidCard')
            ->find($memberId);

        if ($member === null) {
            Log::warning('Skipping ZKTeco access removal for missing member', [
                'member_id' => $memberId,
            ]);

            return false;
        }

        if ($rfidCardId !== null) {
            return $this->queueDeviceAccessRemoval($member, (string) $rfidCardId);
        }

        if ($member->activeRfidCard === null) {
            Log::info('Skipping ZKTeco access removal without RFID card', [
                'member_id' => $member->id,
            ]);

            return false;
        }

        return $this->queueDeviceAccessRemoval($member, (string) $member->activeRfidCard->id);
    }

    public function revokeMemberAccess(Member $member): bool
    {
        $member->loadMissing('activeRfidCard');

        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        $queuedAnyCommand = $member->activeRfidCard !== null
            ? $this->queueDeviceAccessRemoval($member, (string) $member->activeRfidCard->id)
            : false;

        $this->transaction(function () use ($member): void {
            if ($member->activeRfidCard !== null) {
                $this->disableAllCardsForMember($member);
            }

            $this->markMemberExpired($member);
        });

        return $queuedAnyCommand || $this->hasRevokedAccessOnAllDevices($member, $activeDevices);
    }

    private function queueDeviceAccessRemoval(Member $member, string $pim): bool
    {
        $activeDevices = ZktecoDevice::query()
            ->where('status', ZktecoDeviceStatus::Active)
            ->orderBy('serial_number')
            ->get();

        if ($activeDevices->isEmpty()) {
            Log::info('No active ZKTeco devices available for access removal', [
                'member_id' => $member->id,
                'pim' => $pim,
            ]);

            return false;
        }

        $queuedAnyCommand = false;

        $this->transaction(function () use ($member, $pim, $activeDevices, &$queuedAnyCommand): void {
            foreach ($activeDevices as $device) {
                if ($this->hasRevokedAccess($member, $device)) {
                    continue;
                }

                if ($this->hasPendingRemovalCommand($device, $pim)) {
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

                $command = $this->devices->deleteUser($device, $pim);

                MemberZktecoAccessRemoval::query()->create([
                    'member_id' => $member->id,
                    'serial_number' => $device->serial_number,
                    'zkteco_command_id' => $command->id,
                    'revoked_at' => now(),
                ]);

                $queuedAnyCommand = true;

                Log::info('Queued ZKTeco user removal for member', [
                    'member_id' => $member->id,
                    'pim' => $pim,
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

    private function hasPendingRemovalCommand(ZktecoDevice $device, string $pim): bool
    {
        $deleteCommand = 'DATA DELETE user Pin='.$pim;

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
