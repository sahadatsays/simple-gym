<?php

namespace App\Console\Commands;

use App\Services\GymNotificationService;
use Illuminate\Console\Command;

class SyncGymNotificationsCommand extends Command
{
    protected $signature = 'notifications:sync-gym-alerts';

    protected $description = 'Sync gym alert notifications for all active users';

    public function handle(GymNotificationService $notifications): int
    {
        $count = $notifications->syncForAllUsers();

        $this->info("Synced gym alert notifications for {$count} users.");

        return self::SUCCESS;
    }
}
