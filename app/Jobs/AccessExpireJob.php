<?php

namespace App\Jobs;

use App\Services\MemberDeviceAccessService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccessExpireJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return 'access-expire';
    }

    public function handle(MemberDeviceAccessService $memberDeviceAccess): void
    {
        $processed = $memberDeviceAccess->revokeExpiredMemberAccess();

        Log::info('AccessExpireJob completed', [
            'processed_members' => $processed,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('AccessExpireJob failed', [
            'message' => $exception?->getMessage(),
        ]);
    }
}
