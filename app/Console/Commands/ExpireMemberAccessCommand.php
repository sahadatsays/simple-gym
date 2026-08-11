<?php

namespace App\Console\Commands;

use App\Jobs\AccessExpireJob;
use Illuminate\Console\Command;

class ExpireMemberAccessCommand extends Command
{
    protected $signature = 'access:expire';

    protected $description = 'Queue removal of expired member access from ZKTeco devices';

    public function handle(): int
    {
        AccessExpireJob::dispatch();

        $this->info('Queued expired member access removal job.');

        return self::SUCCESS;
    }
}
