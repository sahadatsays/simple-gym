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
     * Remaining balance is derived from repayment history and is not stored.
     *
     * @return Attribute<float, never>
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::get(function (): float {
            $repaid = $this->relationLoaded('repayments')
                ? (float) $this->repayments->sum('amount')
                : (float) $this->repayments()->sum('amount');

            return Money::round((float) $this->amount - $repaid);
        });
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
