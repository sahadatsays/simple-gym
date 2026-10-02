<?php

namespace App\Services;

use App\Contracts\Repositories\BorrowingRepositoryInterface;
use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Support\ActivityLogger;
use App\Support\Money;
use InvalidArgumentException;

class BorrowingService extends BaseService
{
    public function __construct(
        private BorrowingRepositoryInterface $borrowings,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null): Borrowing
    {
        return $this->transaction(function () use ($data, $createdBy): Borrowing {
            $payload = $data;
            $payload['borrowing_no'] = $this->borrowings->nextBorrowingNumber();
            $payload['status'] = BorrowingStatus::Active->value;
            $payload['created_by'] = $createdBy;

            $borrowing = $this->borrowings->create($payload);

            $this->activityLogger->log('borrowing.created', $borrowing, 'Borrowing created', [
                'borrowing_no' => $borrowing->borrowing_no,
                'amount' => $borrowing->amount,
            ]);

            return $borrowing;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Borrowing $borrowing, array $data): Borrowing
    {
        return $this->transaction(function () use ($borrowing, $data): Borrowing {
            $this->assertAmountCoversRepayments($borrowing, (float) $data['amount']);

            $updatedBorrowing = $this->borrowings->update($borrowing, $data);

            $this->activityLogger->log('borrowing.updated', $updatedBorrowing, 'Borrowing updated', [
                'borrowing_no' => $updatedBorrowing->borrowing_no,
            ]);

            return $updatedBorrowing;
        });
    }

    public function delete(Borrowing $borrowing): void
    {
        if ($borrowing->repayments()->exists()) {
            throw new InvalidArgumentException('This borrowing has repayment history and cannot be deleted.');
        }

        $this->transaction(function () use ($borrowing): void {
            $this->activityLogger->log('borrowing.deleted', $borrowing, 'Borrowing deleted', [
                'borrowing_no' => $borrowing->borrowing_no,
            ]);

            $this->borrowings->delete($borrowing);
        });
    }

    private function assertAmountCoversRepayments(Borrowing $borrowing, float $amount): void
    {
        $repaid = (float) $borrowing->repayments()->sum('amount');

        if (Money::greaterThan($repaid, $amount)) {
            throw new InvalidArgumentException('Amount cannot be less than the amount already returned.');
        }
    }
}
