<?php

namespace App\Models;

use App\Enums\RfidCardStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'rfid_card_id',
    'issue_date',
    'return_date',
    'card_fee',
    'deposit_amount',
    'status',
    'invoice_id',
    'open_card_id',
])]
class RfidCardAssignment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
            'return_date' => 'datetime',
            'card_fee' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'status' => RfidCardStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<RfidCard, $this>
     */
    public function rfidCard(): BelongsTo
    {
        return $this->belongsTo(RfidCard::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isOpen(): bool
    {
        return $this->return_date === null;
    }
}
