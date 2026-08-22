<?php

namespace App\Console\Commands;

use App\Jobs\MemberAccessRestrictionEndJob;
use App\Jobs\MemberAccessRestrictionStartJob;
use App\Services\GymSettingService;
use App\Support\MemberAccessRestrictionWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DispatchMemberAccessRestrictionJobsCommand extends Command
{
    protected $signature = 'access:restriction:dispatch';

    protected $description = 'Dispatch member access restriction boundary jobs when configured start or end times are reached';

    public function handle(
        GymSettingService $gymSettings,
        MemberAccessRestrictionWindow $restrictionWindow,
    ): int {
        $settings = $gymSettings->get();

        if (! $settings->member_access_restriction_enabled) {
            return self::SUCCESS;
        }

        $startTime = $restrictionWindow->formattedStartTime($settings);
        $endTime = $restrictionWindow->formattedEndTime($settings);

        if ($startTime === null || $endTime === null) {
            return self::SUCCESS;
        }

        $now = $restrictionWindow->now($settings);
        $status = $restrictionWindow->status($settings, $now);
        $periodKey = $restrictionWindow->periodKey($settings, $now);
        $currentTime = $now->format('H:i');

        if ($periodKey === null) {
            return self::SUCCESS;
        }

        if ($status['active']) {
            $this->logCountdown($status);

            $this->dispatchBoundaryJob(
                'start',
                $periodKey,
                fn (string $boundaryKey) => MemberAccessRestrictionStartJob::dispatch($boundaryKey),
                [
                    'event' => 'start',
                    'configured_start_time' => $startTime,
                    'configured_end_time' => $endTime,
                    'timezone' => $status['timezone'],
                    'at' => $now->toDateTimeString(),
                ],
            );
        }

        $shouldEnd = $currentTime === $endTime
            || (
                ! $status['active']
                && Cache::has($this->cacheKey('start', $periodKey))
                && ! Cache::has($this->cacheKey('end', $periodKey))
                && $now->gte($restrictionWindow->periodEndAt($settings, $now) ?? $now->copy()->addDay())
            );

        if ($shouldEnd) {
            $this->dispatchBoundaryJob(
                'end',
                $periodKey,
                fn (string $boundaryKey) => MemberAccessRestrictionEndJob::dispatch($boundaryKey),
                [
                    'event' => 'end',
                    'configured_start_time' => $startTime,
                    'configured_end_time' => $endTime,
                    'timezone' => $status['timezone'],
                    'at' => $now->toDateTimeString(),
                ],
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param  callable(string): void  $dispatcher
     * @param  array<string, mixed>  $context
     */
    private function dispatchBoundaryJob(string $type, string $boundaryKey, callable $dispatcher, array $context): void
    {
        $cacheKey = $this->cacheKey($type, $boundaryKey);

        if (! Cache::add($cacheKey, true, now()->addDay())) {
            return;
        }

        Log::info('Device access restriction '.$type.' time reached — queuing job', [
            ...$context,
            'boundary_key' => $boundaryKey,
        ]);

        $dispatcher($boundaryKey);

        $this->info("Queued member access restriction {$type} job for {$boundaryKey}.");
    }

    /**
     * @param  array{
     *     start_time: ?string,
     *     end_time: ?string,
     *     timezone: string,
     *     seconds_remaining: ?int,
     *     remaining_label: ?string
     * }  $status
     */
    private function logCountdown(array $status): void
    {
        $message = sprintf(
            'Device access restriction ACTIVE — %s until cards are restored (window %s–%s, %s)',
            $status['remaining_label'] ?? 'unknown time remaining',
            $status['start_time'] ?? '?',
            $status['end_time'] ?? '?',
            $status['timezone'],
        );

        Log::info($message, [
            'seconds_remaining' => $status['seconds_remaining'],
            'start_time' => $status['start_time'],
            'end_time' => $status['end_time'],
            'timezone' => $status['timezone'],
        ]);

        $this->line($message);
    }

    private function cacheKey(string $type, string $boundaryKey): string
    {
        return 'member-access-restriction:'.$type.':'.$boundaryKey;
    }
}
