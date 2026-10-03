<?php

namespace App\Models;

use App\Enums\LockerReservationStatus;
use Database\Factories\LockerReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'locker_id',
    'member_id',
    'start_date',
    'end_date',
    'monthly_fee',
    'status',
    'invoice_id',
    'created_by',
])]
class LockerReservation extends Model
{
    /** @use HasFactory<LockerReservationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_fee' => 'decimal:2',
            'status' => LockerReservationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Locker, $this>
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === LockerReservationStatus::Active;
    }

    /**
     * Reservations that still need a next month: already expired, or ending within the reminder window.
     * A reservation that already has a later period is left off the list.
     *
     * @param  Builder<LockerReservation>  $query
     * @return Builder<LockerReservation>
     */
    public function scopeRenewalReview(Builder $query, int $reminderDays): Builder
    {
        $reviewUntil = today()->addDays($reminderDays);
        $table = $query->getModel()->getTable();

        return $query
            ->where('status', '!=', LockerReservationStatus::Cancelled)
            ->whereDate('end_date', '<=', $reviewUntil)
            ->whereNotExists(function ($later) use ($table): void {
                $later->selectRaw('1')
                    ->from($table.' as later_reservations')
                    ->whereColumn('later_reservations.locker_id', $table.'.locker_id')
                    ->where('later_reservations.status', '!=', LockerReservationStatus::Cancelled->value)
                    ->whereColumn('later_reservations.start_date', '>', $table.'.end_date');
            });
    }

    public function daysUntilExpiry(): int
    {
        return (int) today()->diffInDays($this->end_date, false);
    }
}
