<?php

namespace App\Console\Commands;

use App\Jobs\MemberAccessRestrictionEndJob;
use App\Jobs\MemberAccessRestrictionStartJob;
use App\Services\GymSettingService;
use App\Support\MemberAccessRestrictionWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
        $currentTime = $now->format('H:i');

        if ($currentTime === $startTime) {
            $this->dispatchBoundaryJob(
                'start',
                $now->toDateString().':'.$startTime,
                fn (string $boundaryKey) => MemberAccessRestrictionStartJob::dispatch($boundaryKey),
            );
        }

        if ($currentTime === $endTime) {
            $this->dispatchBoundaryJob(
                'end',
                $now->toDateString().':'.$endTime,
                fn (string $boundaryKey) => MemberAccessRestrictionEndJob::dispatch($boundaryKey),
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param  callable(string): void  $dispatcher
     */
    private function dispatchBoundaryJob(string $type, string $boundaryKey, callable $dispatcher): void
    {
        $cacheKey = 'member-access-restriction:'.$type.':'.$boundaryKey;

        if (! Cache::add($cacheKey, true, now()->addDay())) {
            return;
        }

        $dispatcher($boundaryKey);

        $this->info("Queued member access restriction {$type} job for {$boundaryKey}.");
    }
}
