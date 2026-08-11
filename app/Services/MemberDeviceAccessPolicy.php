<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\MemberAccessRestrictionGroup;
use App\Models\Member;
use App\Models\RfidCard;
use App\Support\MemberAccessRestrictionWindow;

class MemberDeviceAccessPolicy
{
    public function __construct(
        private GymSettingService $gymSettings,
        private MemberAccessRestrictionWindow $restrictionWindow,
    ) {}

    public function isRestrictionEnabled(): bool
    {
        return (bool) $this->gymSettings->get()->member_access_restriction_enabled;
    }

    public function isRestrictionWindowActive(): bool
    {
        return $this->restrictionWindow->isActive($this->gymSettings->get());
    }

    public function configuredGroup(): MemberAccessRestrictionGroup
    {
        return $this->gymSettings->get()->member_access_restriction_group
            ?? MemberAccessRestrictionGroup::Male;
    }

    public function isMemberInConfiguredGroup(Member $member): bool
    {
        return $this->isMemberInGroup($member, $this->configuredGroup());
    }

    public function isMemberInGroup(Member $member, MemberAccessRestrictionGroup $group): bool
    {
        return match ($group) {
            MemberAccessRestrictionGroup::Male => $member->gender === Gender::Male,
        };
    }

    public function isMemberCurrentlyRestricted(Member $member): bool
    {
        if (! $this->isRestrictionEnabled() || ! $this->isRestrictionWindowActive()) {
            return false;
        }

        return $this->isMemberInConfiguredGroup($member);
    }

    public function hasEligibleMembershipAccess(Member $member): bool
    {
        if (! $member->isActive()) {
            return false;
        }

        if (trim($member->member_code) === '') {
            return false;
        }

        $card = $member->relationLoaded('activeRfidCard')
            ? $member->activeRfidCard
            : $member->activeRfidCard()->first();

        return $card instanceof RfidCard && $card->isActive();
    }

    public function canSyncToDevice(Member $member): bool
    {
        return $this->hasEligibleMembershipAccess($member)
            && ! $this->isMemberCurrentlyRestricted($member);
    }

    public function shouldRevokeFromDevice(Member $member): bool
    {
        if (! $this->isMemberCurrentlyRestricted($member)) {
            return false;
        }

        return $this->hasEligibleMembershipAccess($member);
    }
}
