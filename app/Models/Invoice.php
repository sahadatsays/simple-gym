<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    'due_at',
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
            'due_at' => 'datetime',
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
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany('paid_at');
    }

    /**
     * @return HasMany<ProductSale, $this>
     */
    public function productSales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
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

    public function isPartiallyPaid(): bool
    {
        return $this->status === InvoiceStatus::Partial;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true);
    }

    public function isPosSale(): bool
    {
        return $this->type === InvoiceType::PosSale;
    }

    public function canBeDeletedToday(): bool
    {
        return $this->issued_at !== null && $this->issued_at->isToday();
    }

    public function isRegistration(): bool
    {
        return $this->type === InvoiceType::Registration;
    }

    public function amountPaid(): float
    {
        if ($this->relationLoaded('payments')) {
            return Money::round((float) $this->payments->sum('amount'));
        }

        return Money::round((float) $this->payments()->sum('amount'));
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

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);
    }

    /**
     * Outstanding balance for one member, correlated to members.id.
     *
     * @return Builder<Invoice>
     */
    public static function correlatedOutstandingSubquery(): Builder
    {
        return static::query()
            ->selectRaw(
                'COALESCE(SUM('.static::outstandingBalanceSql().'), 0)',
                [PaymentStatus::Completed->value],
            )
            ->whereColumn('invoices.member_id', 'members.id')
            ->open();
    }

    /**
     * @param  callable(Builder<Invoice>): void  $constraint
     */
    public static function sumOutstanding(callable $constraint): float
    {
        $query = static::query()
            ->selectRaw(
                'COALESCE(SUM('.static::outstandingBalanceSql().'), 0) as outstanding_due',
                [PaymentStatus::Completed->value],
            )
            ->open();

        $constraint($query);

        return Money::round((float) ($query->first()?->outstanding_due ?? 0));
    }

    private static function outstandingBalanceSql(): string
    {
        return 'invoices.total - COALESCE((SELECT SUM(payments.amount) FROM payments WHERE payments.invoice_id = invoices.id AND payments.status = ?), 0)';
    }
}
