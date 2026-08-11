<?php

namespace App\Support;

use App\Models\GymSetting;
use Illuminate\Support\Carbon;

class MemberAccessRestrictionWindow
{
    public function now(GymSetting $settings): Carbon
    {
        return now($settings->timezone ?? config('app.timezone'));
    }

    public function isActive(GymSetting $settings, ?Carbon $at = null): bool
    {
        if (! $settings->member_access_restriction_enabled) {
            return false;
        }

        if ($settings->member_access_restriction_start_time === null
            || $settings->member_access_restriction_end_time === null) {
            return false;
        }

        $at ??= $this->now($settings);

        $start = $this->timeOnDate($settings->member_access_restriction_start_time, $at);
        $end = $this->timeOnDate($settings->member_access_restriction_end_time, $at);

        if ($start->equalTo($end)) {
            return false;
        }

        if ($start->lt($end)) {
            return $at->gte($start) && $at->lt($end);
        }

        return $at->gte($start) || $at->lt($end);
    }

    public function formattedStartTime(GymSetting $settings): ?string
    {
        return $this->formattedTime($settings->member_access_restriction_start_time);
    }

    public function formattedEndTime(GymSetting $settings): ?string
    {
        return $this->formattedTime($settings->member_access_restriction_end_time);
    }

    private function formattedTime(mixed $time): ?string
    {
        if ($time === null) {
            return null;
        }

        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        return Carbon::parse($time)->format('H:i');
    }

    private function timeOnDate(mixed $time, Carbon $date): Carbon
    {
        $timeString = $time instanceof Carbon
            ? $time->format('H:i:s')
            : Carbon::parse($time)->format('H:i:s');

        return $date->copy()->setTimeFromTimeString($timeString);
    }
}
