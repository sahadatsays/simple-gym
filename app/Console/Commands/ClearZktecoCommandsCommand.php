<?php

namespace App\Console\Commands;

use App\Services\ZktecoCommandCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class ClearZktecoCommandsCommand extends Command
{
    protected $signature = 'zkteco:clear-commands
                            {--before= : Delete commands created before this date (Y-m-d). Defaults to start of today}
                            {--dry-run : Count matching commands without deleting them}
                            {--include-pending : Also delete pending commands (not recommended)}';

    protected $description = 'Clear historical ZKTeco device commands from previous days';

    public function handle(ZktecoCommandCleanupService $cleanup): int
    {
        try {
            $cutoff = $this->resolveCutoff();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $preservePending = ! $this->option('include-pending');

        if ($this->option('dry-run')) {
            $preview = $cleanup->previewOlderThan($cutoff, $preservePending);

            $this->info("Dry run: {$preview['eligible']} command(s) would be deleted (created before {$cutoff->toDateTimeString()}).");

            if ($preservePending) {
                $this->line("Pending commands preserved: {$preview['preserved_pending']}.");
            }

            return self::SUCCESS;
        }

        $result = $cleanup->purgeOlderThan($cutoff, $preservePending);

        $this->info("Deleted {$result['deleted']} historical ZKTeco command(s) created before {$cutoff->toDateTimeString()}.");

        if ($preservePending) {
            $this->line("Pending commands preserved: {$result['preserved_pending']}.");
        }

        return self::SUCCESS;
    }

    private function resolveCutoff(): Carbon
    {
        $before = $this->option('before');

        if ($before === null || $before === '') {
            return now()->startOfDay();
        }

        if (! is_string($before) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $before)) {
            throw new InvalidArgumentException('The --before option must be a date in Y-m-d format.');
        }

        return Carbon::createFromFormat('Y-m-d', $before)->startOfDay();
    }
}
