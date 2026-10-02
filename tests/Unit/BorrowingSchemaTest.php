<?php

use App\Enums\BorrowingStatus;
use App\Enums\PaymentMethod;
use App\Models\Borrowing;
use App\Models\BorrowingRepayment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates borrowing tables without a stored remaining amount', function () {
    expect(Schema::hasTable('borrowings'))->toBeTrue()
        ->and(Schema::hasColumns('borrowings', [
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
        ]))->toBeTrue()
        ->and(Schema::hasColumn('borrowings', 'remaining_amount'))->toBeFalse()
        ->and(Schema::hasTable('borrowing_repayments'))->toBeTrue()
        ->and(Schema::hasColumns('borrowing_repayments', [
            'repayment_no',
            'borrowing_id',
            'repayment_date',
            'amount',
            'payment_method',
            'description',
            'created_by',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('borrowing_repayments', 'remaining_amount'))->toBeFalse();
});

it('derives the remaining amount from repayment history', function () {
    $borrowing = Borrowing::factory()->create([
        'amount' => 2000,
        'status' => BorrowingStatus::PartiallyRepaid,
    ]);

    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 500,
    ]);
    BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
        'amount' => 250,
    ]);

    $borrowing->refresh()->load('repayments');

    expect($borrowing->repayments)->toHaveCount(2)
        ->and($borrowing->remaining_amount)->toBe(1250.0)
        ->and($borrowing->status)->toBe(BorrowingStatus::PartiallyRepaid)
        ->and($borrowing->payment_method)->toBeInstanceOf(PaymentMethod::class);
});

it('keeps repayment history when a borrowing is deleted', function () {
    $borrowing = Borrowing::factory()->create();
    $repayment = BorrowingRepayment::factory()->create([
        'borrowing_id' => $borrowing->id,
    ]);

    expect(fn () => $borrowing->delete())->toThrow(QueryException::class);

    expect(Borrowing::query()->whereKey($borrowing->id)->exists())->toBeTrue()
        ->and(BorrowingRepayment::query()->whereKey($repayment->id)->exists())->toBeTrue();
});
