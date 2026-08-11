<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\MemberAccessRestrictionGroup;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;

class MemberAccessRestrictionService extends BaseService
{
    public function __construct(
        private MemberDeviceAccessPolicy $accessPolicy,
        private MemberDeviceAccessService $deviceAccess,
    ) {}

    public function applyRestrictionStart(): int
    {
        if (! $this->accessPolicy->isRestrictionEnabled() || ! $this->accessPolicy->isRestrictionWindowActive()) {
            return 0;
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

        return $processed;
    }

    public function applyRestrictionEnd(): int
    {
        if (! $this->accessPolicy->isRestrictionEnabled() || $this->accessPolicy->isRestrictionWindowActive()) {
            return 0;
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

        return $processed;
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
