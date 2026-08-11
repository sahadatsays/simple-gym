<?php

namespace App\Jobs;

use App\Services\MemberDeviceAccessService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberAccessRevokeJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public int $uniqueFor = 60;

    public function __construct(public int $memberId, public int $rfidCardId) {}

    public function uniqueId(): string
    {
        return 'member-access-revoke-'.$this->memberId.'-'.$this->rfidCardId;
    }

    public function handle(MemberDeviceAccessService $memberDeviceAccess): void
    {
        $revoked = $memberDeviceAccess->revokeMemberDeviceAccess($this->memberId, $this->rfidCardId);

        Log::info('MemberAccessRevokeJob completed', [
            'member_id' => $this->memberId,
            'rfid_card_id' => $this->rfidCardId,
            'revoked' => $revoked,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('MemberAccessRevokeJob failed', [
            'member_id' => $this->memberId,
            'rfid_card_id' => $this->rfidCardId,
            'message' => $exception?->getMessage(),
        ]);
    }
}
