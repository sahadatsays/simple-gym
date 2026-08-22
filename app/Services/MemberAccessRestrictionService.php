<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\MemberAccessRestrictionGroup;
use App\Models\Member;
use App\Support\MemberAccessRestrictionWindow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MemberAccessRestrictionService extends BaseService
{
    public function __construct(
        private MemberDeviceAccessPolicy $accessPolicy,
        private MemberDeviceAccessService $deviceAccess,
        private GymSettingService $gymSettings,
        private MemberAccessRestrictionWindow $restrictionWindow,
    ) {}

    /**
     * @return array{processed: int, group: string, start_time: ?string, end_time: ?string}
     */
    public function applyRestrictionStart(): array
    {
        $settings = $this->gymSettings->get();
        $status = $this->restrictionWindow->status($settings);

        Log::info('Device access restriction START applied', [
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
            'timezone' => $status['timezone'],
            'group' => $this->accessPolicy->configuredGroup()->value,
            'window_active' => $status['active'],
        ]);

        if (! $this->accessPolicy->isRestrictionEnabled() || ! $this->accessPolicy->isRestrictionWindowActive()) {
            Log::warning('Device access restriction START skipped because the window is not active', $status);

            return $this->emptyResult();
        }

        $processed = 0;

        $this->membersInConfiguredGroupQuery()
            ->active()
            ->whereHas('activeRfidCard')
            ->with('activeRfidCard')
            ->orderBy('id')
            ->chunkById(100, function ($members) use (&$processed): void {
                foreach ($members as $member) {
                    if (! $this->accessPolicy->shouldRevokeFromDevice($member)) {
                        continue;
                    }

                    if ($this->deviceAccess->isMemberRemovedFromAllActiveDevices($member)) {
                        continue;
                    }

                    if ($this->deviceAccess->revokeMemberDeviceAccess($member->id)) {
                        $processed++;
                    }
                }
            });

        Log::info('Device access restriction START completed — restricted group cards blocked on devices', [
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
            'timezone' => $status['timezone'],
            'group' => $this->accessPolicy->configuredGroup()->value,
            'members_blocked' => $processed,
            'remaining' => $status['remaining_label'],
        ]);

        return [
            'processed' => $processed,
            'group' => $this->accessPolicy->configuredGroup()->value,
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
        ];
    }

    /**
     * @return array{processed: int, group: string, start_time: ?string, end_time: ?string}
     */
    public function applyRestrictionEnd(): array
    {
        $settings = $this->gymSettings->get();
        $status = $this->restrictionWindow->status($settings);

        Log::info('Device access restriction END applied', [
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
            'timezone' => $status['timezone'],
            'group' => $this->accessPolicy->configuredGroup()->value,
            'window_active' => $status['active'],
        ]);

        if (! $this->accessPolicy->isRestrictionEnabled() || $this->accessPolicy->isRestrictionWindowActive()) {
            Log::warning('Device access restriction END skipped because the window is still active', $status);

            return $this->emptyResult();
        }

        $processed = 0;

        $this->membersInConfiguredGroupQuery()
            ->with('activeRfidCard')
            ->orderBy('id')
            ->chunkById(100, function ($members) use (&$processed): void {
                foreach ($members as $member) {
                    if (! $this->accessPolicy->canSyncToDevice($member)) {
                        continue;
                    }

                    if ($this->deviceAccess->grantMemberAccess($member->id)) {
                        $processed++;
                    }
                }
            });

        Log::info('Device access restriction END completed — eligible cards restored and should work on devices again', [
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
            'timezone' => $status['timezone'],
            'group' => $this->accessPolicy->configuredGroup()->value,
            'members_restored' => $processed,
            'message' => 'All eligible restricted-group cards were re-synced to active ZKTeco devices.',
        ]);

        return [
            'processed' => $processed,
            'group' => $this->accessPolicy->configuredGroup()->value,
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
        ];
    }

    /**
     * @return array{processed: int, group: string, start_time: ?string, end_time: ?string}
     */
    private function emptyResult(): array
    {
        $settings = $this->gymSettings->get();

        return [
            'processed' => 0,
            'group' => $this->accessPolicy->configuredGroup()->value,
            'start_time' => $this->restrictionWindow->formattedStartTime($settings),
            'end_time' => $this->restrictionWindow->formattedEndTime($settings),
        ];
    }

    /**
     * @return Builder<Member>
     */
    private function membersInConfiguredGroupQuery(): Builder
    {
        return match ($this->accessPolicy->configuredGroup()) {
            MemberAccessRestrictionGroup::Male => Member::query()->where('gender', Gender::Male),
        };
    }
}
