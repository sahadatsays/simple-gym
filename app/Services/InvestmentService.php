<?php

namespace App\Services;

use App\Contracts\Repositories\InvestmentRepositoryInterface;
use App\Models\Investment;
use App\Support\ActivityLogger;
use App\Support\InvestmentAttachmentStorage;
use Illuminate\Http\UploadedFile;

class InvestmentService extends BaseService
{
    public function __construct(
        private InvestmentRepositoryInterface $investments,
        private InvestmentAttachmentStorage $attachmentStorage,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null, ?int $createdBy = null): Investment
    {
        return $this->transaction(function () use ($data, $attachment, $createdBy): Investment {
            $payload = $data;
            $payload['investment_number'] = $this->investments->nextInvestmentNumber();
            $payload['created_by'] = $createdBy;

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $investment = $this->investments->create($payload);

            $this->activityLogger->log('investment.created', $investment, 'Investment created', [
                'investment_number' => $investment->investment_number,
                'amount' => $investment->amount,
            ]);

            return $investment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Investment $investment, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Investment
    {
        return $this->transaction(function () use ($investment, $data, $attachment, $removeAttachment): Investment {
            $payload = $data;
            $previousAttachment = $investment->attachment_path;

            if ($removeAttachment) {
                $payload['attachment_path'] = null;
            }

            if ($attachment !== null) {
                $payload['attachment_path'] = $this->attachmentStorage->store($attachment);
            }

            $updatedInvestment = $this->investments->update($investment, $payload);

            if ($previousAttachment !== null && ($removeAttachment || $attachment !== null)) {
                $this->attachmentStorage->delete($previousAttachment);
            }

            $this->activityLogger->log('investment.updated', $updatedInvestment, 'Investment updated', [
                'investment_number' => $updatedInvestment->investment_number,
            ]);

            return $updatedInvestment;
        });
    }

    public function delete(Investment $investment): void
    {
        $this->transaction(function () use ($investment): void {
            $this->activityLogger->log('investment.deleted', $investment, 'Investment deleted', [
                'investment_number' => $investment->investment_number,
            ]);

            $attachmentPath = $investment->attachment_path;

            $this->investments->delete($investment);

            $this->attachmentStorage->delete($attachmentPath);
        });
    }
}
