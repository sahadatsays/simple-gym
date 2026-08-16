<?php

namespace App\Services;

use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Models\Expense;
use App\Support\ActivityLogger;
use App\Support\ExpenseAttachmentStorage;
use Illuminate\Http\UploadedFile;

class ExpenseService extends BaseService
{
    public function __construct(
        private ExpenseRepositoryInterface $expenses,
        private ExpenseAttachmentStorage $attachmentStorage,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null, ?int $createdBy = null): Expense
    {
        return $this->transaction(function () use ($data, $attachment, $createdBy): Expense {
            $payload = $data;
            $payload['expense_number'] = $this->expenses->nextExpenseNumber();
            $payload['created_by'] = $createdBy;

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $expense = $this->expenses->create($payload);

            $this->activityLogger->log('expense.created', $expense, 'Expense created', [
                'expense_number' => $expense->expense_number,
                'amount' => $expense->amount,
            ]);

            return $expense;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Expense
    {
        return $this->transaction(function () use ($expense, $data, $attachment, $removeAttachment): Expense {
            $payload = $data;
            $previousAttachment = $expense->attachment_path;

            if ($removeAttachment) {
                $payload['attachment_path'] = null;
            }

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $updatedExpense = $this->expenses->update($expense, $payload);

            if ($previousAttachment !== null && ($removeAttachment || $attachment !== null)) {
                $this->attachmentStorage->delete($previousAttachment);
            }

            $this->activityLogger->log('expense.updated', $updatedExpense, 'Expense updated', [
                'expense_number' => $updatedExpense->expense_number,
            ]);

            return $updatedExpense;
        });
    }

    public function delete(Expense $expense): void
    {
        $this->transaction(function () use ($expense): void {
            $this->activityLogger->log('expense.deleted', $expense, 'Expense deleted', [
                'expense_number' => $expense->expense_number,
            ]);

            $attachmentPath = $expense->attachment_path;

            $this->expenses->delete($expense);

            $this->attachmentStorage->delete($attachmentPath);
        });
    }
}
