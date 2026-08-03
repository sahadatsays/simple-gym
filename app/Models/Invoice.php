<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'member_id',
    'membership_plan_id',
    'type',
    'invoice_number',
    'subtotal',
    'discount_amount',
    'total',
    'status',
    'line_items',
    'issued_at',
    'paid_at',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'type' => InvoiceType::class,
            'line_items' => 'array',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return HasOne<MembershipRenewal, $this>
     */
    public function membershipRenewal(): HasOne
    {
        return $this->hasOne(MembershipRenewal::class);
    }

    public function isRenewal(): bool
    {
        return $this->type === InvoiceType::Renewal;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isPosSale(): bool
    {
        return $this->type === InvoiceType::PosSale;
    }

    public function isRegistration(): bool
    {
        return $this->type === InvoiceType::Registration;
    }

    public function amountPaid(): float
    {
        if ($this->relationLoaded('payment') && $this->payment !== null) {
            return Money::round((float) $this->payment->amount);
        }

        $paid = $this->payment()->value('amount');

        return Money::round((float) ($paid ?? 0));
    }

    public function outstandingBalance(): float
    {
        if ($this->isPaid()) {
            return 0.0;
        }

        return Money::round(max(0, (float) $this->total - $this->amountPaid()));
    }

    public function amountDue(): float
    {
        return $this->outstandingBalance();
    }
}
