<?php

namespace App\Models;

use App\Enums\LockerReservationStatus;
use Database\Factories\LockerReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
