<?php

namespace App\Services;

use App\Contracts\Repositories\LockerRepositoryInterface;
use App\Enums\LockerStatus;
use App\Models\Locker;
use App\Support\ActivityLogger;
use App\Support\Money;
use InvalidArgumentException;

class LockerService extends BaseService
{
    public function __construct(
        private LockerRepositoryInterface $lockers,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy): Locker
    {
        return $this->transaction(function () use ($data, $createdBy): Locker {
            $payload = $data;
            $payload['monthly_fee'] = Money::round((float) ($payload['monthly_fee'] ?? 0));
            $payload['created_by'] = $createdBy;

            $locker = $this->lockers->create($payload);

            $this->activityLogger->log('locker.created', $locker, 'Locker created', [
                'locker_number' => $locker->locker_number,
            ]);

            return $locker;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Locker $locker, array $data): Locker
    {
        return $this->transaction(function () use ($locker, $data): Locker {
            $this->assertCanReserve($locker, $data['status'] ?? null);

            $payload = $data;
            $payload['monthly_fee'] = Money::round((float) ($payload['monthly_fee'] ?? 0));

            $updatedLocker = $this->lockers->update($locker, $payload);

            $this->activityLogger->log('locker.updated', $updatedLocker, 'Locker updated', [
                'locker_number' => $updatedLocker->locker_number,
            ]);

            return $updatedLocker;
        });
    }

    public function delete(Locker $locker): void
    {
        $this->transaction(function () use ($locker): void {
            if ($locker->reservations()->exists()) {
                throw new InvalidArgumentException('This locker has reservation history and cannot be deleted.');
            }

            $this->activityLogger->log('locker.deleted', $locker, 'Locker deleted', [
                'locker_number' => $locker->locker_number,
            ]);

            $this->lockers->delete($locker);
        });
    }

    private function assertCanReserve(Locker $locker, mixed $status): void
    {
        if ($status !== LockerStatus::Reserved->value && $status !== LockerStatus::Reserved) {
            return;
        }

        if (! $locker->canBeReserved()) {
            throw new InvalidArgumentException('Disabled or maintenance lockers cannot be reserved.');
        }
    }
}
