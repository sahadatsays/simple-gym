<?php

namespace App\Services;

use App\Models\ZktecoCommand;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ZktecoCommandCleanupService extends BaseService
{
    private const DELETE_CHUNK_SIZE = 500;

    /**
     * Delete historical device commands created before the cutoff.
     *
     * Pending commands are preserved by default so undelivered work is never lost.
     *
     * @return array{deleted: int, cutoff: CarbonInterface, preserved_pending: int}
     */
    public function purgeOlderThan(CarbonInterface $cutoff, bool $preservePending = true): array
    {
        $preservedPending = $preservePending
            ? $this->pendingCountOlderThan($cutoff)
            : 0;

        $deleted = $this->transaction(function () use ($cutoff, $preservePending): int {
            $totalDeleted = 0;

            do {
                $ids = $this->eligibleQuery($cutoff, $preservePending)
                    ->orderBy('id')
                    ->limit(self::DELETE_CHUNK_SIZE)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                $totalDeleted += ZktecoCommand::query()
                    ->whereIn('id', $ids)
                    ->delete();
            } while ($ids->count() === self::DELETE_CHUNK_SIZE);

            return $totalDeleted;
        });

        Log::info('ZKTeco historical device commands purged', [
            'cutoff' => $cutoff->toIso8601String(),
            'deleted' => $deleted,
            'preserved_pending' => $preservedPending,
        ]);

        return [
            'deleted' => $deleted,
            'cutoff' => $cutoff,
            'preserved_pending' => $preservedPending,
        ];
    }

    /**
     * Purge commands from previous calendar days (before start of today).
     *
     * @return array{deleted: int, cutoff: CarbonInterface, preserved_pending: int}
     */
    public function purgePreviousDays(): array
    {
        return $this->purgeOlderThan(now()->startOfDay());
    }

    /**
     * @return array{eligible: int, preserved_pending: int, cutoff: CarbonInterface}
     */
    public function previewOlderThan(CarbonInterface $cutoff, bool $preservePending = true): array
    {
        return [
            'eligible' => $this->eligibleQuery($cutoff, $preservePending)->count(),
            'preserved_pending' => $preservePending ? $this->pendingCountOlderThan($cutoff) : 0,
            'cutoff' => $cutoff,
        ];
    }

    private function pendingCountOlderThan(CarbonInterface $cutoff): int
    {
        return ZktecoCommand::query()
            ->where('created_at', '<', $cutoff)
            ->where('status', 'pending')
            ->count();
    }

    /**
     * @return Builder<ZktecoCommand>
     */
    private function eligibleQuery(CarbonInterface $cutoff, bool $preservePending): Builder
    {
        return ZktecoCommand::query()
            ->where('created_at', '<', $cutoff)
            ->when($preservePending, fn (Builder $query) => $query->where('status', '!=', 'pending'));
    }
}
