<?php

namespace App\Services;

use App\Enums\LockerReservationStatus;
use App\Enums\LockerStatus;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Support\ActivityLogger;
use App\Support\Money;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class LockerReservationService extends BaseService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
        private ActivityLogger $activityLogger,
    ) {}

    public function reserve(Locker $locker, Member $member, string $month, ?string $paymentMethod, ?int $createdBy): LockerReservation
    {
        return $this->transaction(function () use ($locker, $member, $month, $paymentMethod, $createdBy): LockerReservation {
            $locker = $this->lockLocker($locker);
            $this->expireDueForLocker($locker);
            $locker->refresh();

            $this->assertLockerCanBeReserved($locker);

            $period = $this->periodForMonth($month);
            $this->assertNoOverlap($locker, $period['start_date'], $period['end_date']);

            $monthlyFee = Money::round((float) $locker->monthly_fee);
            $invoice = $this->collectPayment($locker, $member, $period['start_date'], $monthlyFee, $paymentMethod);

            $reservation = LockerReservation::query()->create([
                'locker_id' => $locker->id,
                'member_id' => $member->id,
                'start_date' => $period['start_date'],
                'end_date' => $period['end_date'],
                'monthly_fee' => $monthlyFee,
                'status' => LockerReservationStatus::Active,
                'invoice_id' => $invoice?->id,
                'created_by' => $createdBy,
            ]);

            $this->markLockerReserved($locker);

            $this->activityLogger->log('locker_reservation.created', $reservation, 'Locker reservation activated', [
                'locker_number' => $locker->locker_number,
                'member_code' => $member->member_code,
            ]);

            return $reservation->load(['locker', 'member', 'invoice', 'creator']);
        });
    }

    public function renew(LockerReservation $reservation, string $month, ?string $paymentMethod, ?int $createdBy): LockerReservation
    {
        $reservation->loadMissing(['locker', 'member']);

        if ($reservation->locker === null || $reservation->member === null) {
            throw new InvalidArgumentException('This reservation cannot be renewed.');
        }

        return $this->reserve($reservation->locker, $reservation->member, $month, $paymentMethod, $createdBy);
    }

    public function cancel(LockerReservation $reservation): LockerReservation
    {
        return $this->transaction(function () use ($reservation): LockerReservation {
            $locked = LockerReservation::query()->whereKey($reservation->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isActive()) {
                throw new InvalidArgumentException('Only an active reservation can be cancelled.');
            }

            $locked->update([
                'status' => LockerReservationStatus::Cancelled,
            ]);

            $locker = $this->lockLocker($locked->locker()->first() ?? throw new InvalidArgumentException('The locker for this reservation was not found.'));
            $this->releaseLockerIfIdle($locker);

            $this->activityLogger->log('locker_reservation.cancelled', $locked, 'Locker reservation cancelled', [
                'locker_number' => $locker->locker_number,
            ]);

            return $locked->fresh(['locker', 'member', 'invoice', 'creator']);
        });
    }

    public function expireDueReservations(): void
    {
        $this->transaction(function (): void {
            $lockerIds = LockerReservation::query()
                ->where('status', LockerReservationStatus::Active)
                ->whereDate('end_date', '<', today())
                ->pluck('locker_id')
                ->unique()
                ->values();

            if ($lockerIds->isEmpty()) {
                return;
            }

            LockerReservation::query()
                ->where('status', LockerReservationStatus::Active)
                ->whereDate('end_date', '<', today())
                ->update([
                    'status' => LockerReservationStatus::Expired->value,
                ]);

            foreach ($lockerIds as $lockerId) {
                $locker = Locker::query()->whereKey($lockerId)->lockForUpdate()->first();

                if ($locker !== null) {
                    $this->releaseLockerIfIdle($locker);
                }
            }
        });
    }

    /**
     * @return array{start_date: string, end_date: string}
     */
    public function periodForMonth(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month);

        if ($start === false) {
            throw new InvalidArgumentException('Choose a valid start month.');
        }

        $start = $start->startOfMonth();
        $end = $start->copy()->endOfMonth()->startOfDay();

        if ($start->gt($end)) {
            throw new InvalidArgumentException('The start date must be on or before the end date.');
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    public function nextMonth(LockerReservation $reservation): string
    {
        return $reservation->end_date->copy()->addDay()->format('Y-m');
    }

    private function expireDueForLocker(Locker $locker): void
    {
        LockerReservation::query()
            ->where('locker_id', $locker->id)
            ->where('status', LockerReservationStatus::Active)
            ->whereDate('end_date', '<', today())
            ->lockForUpdate()
            ->update([
                'status' => LockerReservationStatus::Expired->value,
            ]);

        $this->releaseLockerIfIdle($locker);
    }

    private function assertLockerCanBeReserved(Locker $locker): void
    {
        if (! $locker->canBeReserved()) {
            throw new InvalidArgumentException('Disabled or maintenance lockers cannot be reserved.');
        }
    }

    private function assertNoOverlap(Locker $locker, string $startDate, string $endDate): void
    {
        $overlaps = LockerReservation::query()
            ->where('locker_id', $locker->id)
            ->where('status', LockerReservationStatus::Active)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->lockForUpdate()
            ->exists();

        if ($overlaps) {
            throw new InvalidArgumentException('This locker already has an active reservation for that month.');
        }
    }

    private function collectPayment(Locker $locker, Member $member, string $startDate, float $monthlyFee, ?string $paymentMethod): ?Invoice
    {
        if (! Money::greaterThan($monthlyFee, 0)) {
            return null;
        }

        if ($paymentMethod === null || $paymentMethod === '') {
            throw new InvalidArgumentException('Choose a payment method for the locker fee.');
        }

        $monthLabel = Carbon::parse($startDate)->format('F Y');
        $invoice = $this->invoiceService->createLockerInvoice(
            $member,
            sprintf('Locker %s monthly fee (%s)', $locker->locker_number, $monthLabel),
            $monthlyFee,
        );

        if ($invoice === null) {
            return null;
        }

        $this->paymentService->settleInvoice(
            invoice: $invoice,
            member: $member,
            amountPaid: (float) $invoice->total,
            paymentMethod: $paymentMethod,
            type: PaymentType::Locker,
        );

        return $invoice->fresh();
    }

    private function markLockerReserved(Locker $locker): void
    {
        if ($locker->status === LockerStatus::Reserved) {
            return;
        }

        $locker->update([
            'status' => LockerStatus::Reserved,
        ]);
    }

    private function releaseLockerIfIdle(Locker $locker): void
    {
        $locker->refresh();

        if ($locker->status !== LockerStatus::Reserved) {
            return;
        }

        $hasCurrentReservation = LockerReservation::query()
            ->where('locker_id', $locker->id)
            ->where('status', LockerReservationStatus::Active)
            ->whereDate('end_date', '>=', today())
            ->exists();

        if ($hasCurrentReservation) {
            return;
        }

        $locker->update([
            'status' => LockerStatus::Available,
        ]);
    }

    private function lockLocker(Locker $locker): Locker
    {
        $locked = Locker::query()->whereKey($locker->id)->lockForUpdate()->first();

        if ($locked === null) {
            throw new InvalidArgumentException('The selected locker was not found.');
        }

        return $locked;
    }
}
