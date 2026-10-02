<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\BorrowingRepaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'repayment_no',
    'borrowing_id',
    'repayment_date',
    'amount',
    'payment_method',
    'description',
    'created_by',
])]
class BorrowingRepayment extends Model
{
    /** @use HasFactory<BorrowingRepaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repayment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
        ];
    }

    /**
     * @return BelongsTo<Borrowing, $this>
     */
    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
