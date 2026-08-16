<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'expense_number',
    'expensed_at',
    'expense_category_id',
    'amount',
    'payment_method',
    'paid_to',
    'description',
    'attachment_path',
    'status',
    'created_by',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expensed_at' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => ExpenseStatus::class,
        ];
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attachment_path
            ? Storage::disk('public')->url($this->attachment_path)
            : null);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
