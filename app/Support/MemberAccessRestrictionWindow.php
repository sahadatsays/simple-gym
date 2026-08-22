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

        $start = $this->startAt($settings, $at);
        $end = $this->endAt($settings, $at);

        if ($start === null || $end === null || $start->equalTo($end)) {
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

    /**
     * Absolute start datetime for the restriction period related to $at
     * (active period, or the most recently started period when outside the window).
     */
    public function periodStartAt(GymSetting $settings, ?Carbon $at = null): ?Carbon
    {
        $at ??= $this->now($settings);
        $startTime = $this->formattedStartTime($settings);
        $endTime = $this->formattedEndTime($settings);

        if ($startTime === null || $endTime === null) {
            return null;
        }

        $startToday = $this->timeOnDate($startTime, $at);
        $endToday = $this->timeOnDate($endTime, $at);

        if ($startToday->equalTo($endToday)) {
            return null;
        }

        if ($startToday->lt($endToday)) {
            if ($at->lt($startToday)) {
                return $startToday->copy()->subDay();
            }

            return $startToday;
        }

        if ($at->lt($endToday)) {
            return $startToday->copy()->subDay();
        }

        if ($at->gte($startToday)) {
            return $startToday;
        }

        return $startToday->copy()->subDay();
    }

    /**
     * Absolute end datetime for the restriction period that contains (or just ended at) $at.
     */
    public function periodEndAt(GymSetting $settings, ?Carbon $at = null): ?Carbon
    {
        $periodStart = $this->periodStartAt($settings, $at);

        if ($periodStart === null) {
            return null;
        }

        $endTime = $this->formattedEndTime($settings);

        if ($endTime === null) {
            return null;
        }

        $end = $this->timeOnDate($endTime, $periodStart);

        if ($end->lte($periodStart)) {
            $end->addDay();
        }

        return $end;
    }

    public function periodKey(GymSetting $settings, ?Carbon $at = null): ?string
    {
        $periodStart = $this->periodStartAt($settings, $at);
        $startTime = $this->formattedStartTime($settings);
        $endTime = $this->formattedEndTime($settings);

        if ($periodStart === null || $startTime === null || $endTime === null) {
            return null;
        }

        return $periodStart->toDateString().':'.$startTime.'-'.$endTime;
    }

    public function secondsRemaining(GymSetting $settings, ?Carbon $at = null): ?int
    {
        $at ??= $this->now($settings);

        if (! $this->isActive($settings, $at)) {
            return null;
        }

        $end = $this->periodEndAt($settings, $at);

        if ($end === null) {
            return null;
        }

        return max(0, $end->getTimestamp() - $at->getTimestamp());
    }

    public function formatRemaining(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours === 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0 || $hours > 0) {
            $parts[] = $minutes.' '.($minutes === 1 ? 'minute' : 'minutes');
        }

        $parts[] = $secs.' '.($secs === 1 ? 'second' : 'seconds');

        return implode(' ', $parts).' remaining';
    }

    /**
     * @return array{
     *     enabled: bool,
     *     active: bool,
     *     start_time: ?string,
     *     end_time: ?string,
     *     timezone: string,
     *     period_start_at: ?string,
     *     period_end_at: ?string,
     *     seconds_remaining: ?int,
     *     remaining_label: ?string
     * }
     */
    public function status(GymSetting $settings, ?Carbon $at = null): array
    {
        $at ??= $this->now($settings);
        $active = $this->isActive($settings, $at);
        $secondsRemaining = $active ? $this->secondsRemaining($settings, $at) : null;
        $periodStart = $this->periodStartAt($settings, $at);
        $periodEnd = $this->periodEndAt($settings, $at);

        return [
            'enabled' => (bool) $settings->member_access_restriction_enabled,
            'active' => $active,
            'start_time' => $this->formattedStartTime($settings),
            'end_time' => $this->formattedEndTime($settings),
            'timezone' => $settings->timezone ?? config('app.timezone'),
            'period_start_at' => $periodStart?->toIso8601String(),
            'period_end_at' => $periodEnd?->toIso8601String(),
            'seconds_remaining' => $secondsRemaining,
            'remaining_label' => $this->formatRemaining($secondsRemaining),
        ];
    }

    private function startAt(GymSetting $settings, Carbon $at): ?Carbon
    {
        $startTime = $this->formattedStartTime($settings);

        return $startTime === null ? null : $this->timeOnDate($startTime, $at);
    }

    private function endAt(GymSetting $settings, Carbon $at): ?Carbon
    {
        $endTime = $this->formattedEndTime($settings);

        return $endTime === null ? null : $this->timeOnDate($endTime, $at);
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
            : (strlen((string) $time) === 5
                ? $time.':00'
                : Carbon::parse($time)->format('H:i:s'));

        return $date->copy()->setTimeFromTimeString($timeString);
    }
}
