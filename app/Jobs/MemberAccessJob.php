<?php

namespace App\Jobs;

use App\Services\MemberDeviceAccessService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberAccessJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public int $uniqueFor = 60;

    public function __construct(public int $memberId) {}

    public function uniqueId(): string
    {
        return 'member-access-'.$this->memberId;
    }

    public function handle(MemberDeviceAccessService $memberDeviceAccess): void
    {
        $synced = $memberDeviceAccess->grantMemberAccess($this->memberId);

        Log::info('MemberAccessJob completed', [
            'member_id' => $this->memberId,
            'synced' => $synced,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('MemberAccessJob failed', [
            'member_id' => $this->memberId,
            'message' => $exception?->getMessage(),
        ]);
    }
}
