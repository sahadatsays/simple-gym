<?php

namespace App\Models;

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Support\Money;
use Database\Factories\BorrowingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'borrowing_no',
    'lender_name',
    'lender_phone',
    'borrowing_date',
    'amount',
    'purpose',
    'due_date',
    'payment_method',
    'description',
    'status',
    'created_by',
])]
class Borrowing extends Model
{
    /** @use HasFactory<BorrowingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'borrowing_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => BorrowingStatus::class,
        ];
    }

    /**
     * Sum of saved repayments. This is not a stored column.
     *
     * @return Attribute<float, never>
     */
    protected function totalRepaid(): Attribute
    {
        return Attribute::get(fn (): float => $this->repaidAmount());
    }

    /**
     * Remaining balance is derived from repayment history and is not stored.
     *
     * @return Attribute<float, never>
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn (): float => Money::round((float) $this->amount - $this->repaidAmount()));
    }

    public function acceptsRepayment(): bool
    {
        if (! in_array($this->status, [BorrowingStatus::Active, BorrowingStatus::PartiallyRepaid], true)) {
            return false;
        }

        return Money::greaterThan($this->remaining_amount, 0);
    }

    private function repaidAmount(): float
    {
        if ($this->relationLoaded('repayments')) {
            $repaid = (float) $this->repayments->sum(fn (BorrowingRepayment $repayment): float => (float) $repayment->amount);
        } elseif (array_key_exists('repayments_sum_amount', $this->getAttributes())) {
            $repaid = (float) $this->repayments_sum_amount;
        } else {
            $repaid = (float) $this->repayments()->sum('amount');
        }

        return Money::round($repaid);
    }

    /**
     * @return HasMany<BorrowingRepayment, $this>
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(BorrowingRepayment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
