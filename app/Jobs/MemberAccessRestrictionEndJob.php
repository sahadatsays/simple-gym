<?php

namespace App\Jobs;

use App\Services\MemberAccessRestrictionService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberAccessRestrictionEndJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public int $uniqueFor = 300;

    public function __construct(public string $boundaryKey) {}

    public function uniqueId(): string
    {
        return 'member-access-restriction-end-'.$this->boundaryKey;
    }

    public function handle(MemberAccessRestrictionService $restrictionService): void
    {
        $result = $restrictionService->applyRestrictionEnd();

        Log::info('MemberAccessRestrictionEndJob completed — eligible cards should work again', [
            'boundary_key' => $this->boundaryKey,
            'members_restored' => $result['processed'],
            'group' => $result['group'],
            'start_time' => $result['start_time'],
            'end_time' => $result['end_time'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('MemberAccessRestrictionEndJob failed', [
            'boundary_key' => $this->boundaryKey,
            'message' => $exception?->getMessage(),
        ]);
    }
}
