<?php

namespace App\Services;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use App\Support\MemberPhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class MemberService extends BaseService
{
    public function __construct(
        private MemberRepositoryInterface $members,
        private ActivityLogger $activityLogger,
        private RfidCardService $rfidCardService,
        private MemberPhotoStorage $photoStorage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo = null): Member
    {
        return $this->transaction(function () use ($data, $photo): Member {
            unset($data['photo'], $data['remove_photo']);

            $data['member_code'] = $this->members->nextMemberCode();
            $data = $this->applyPlanExpiry($data);

            if ($photo !== null) {
                $data['photo_path'] = $this->storePhoto($photo);
            }

            $member = $this->members->create($data);

            $this->activityLogger->log('member.created', $member, 'Member registered', [
                'member_code' => $member->member_code,
            ]);

            return $member->load('membershipPlan');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Member $member, array $data, ?UploadedFile $photo = null): Member
    {
        return $this->transaction(function () use ($member, $data, $photo): Member {
            $removePhoto = (bool) ($data['remove_photo'] ?? false);
            unset($data['photo'], $data['remove_photo'], $data['member_code']);

            $data = $this->applyPlanExpiry($data);

            if ($photo !== null) {
                $this->deletePhoto($member->photo_path);
                $data['photo_path'] = $this->storePhoto($photo);
            } elseif ($removePhoto) {
                $this->deletePhoto($member->photo_path);
                $data['photo_path'] = null;
            }

            $updatedMember = $this->members->update($member, $data);

            $this->activityLogger->log('member.updated', $updatedMember, 'Member profile updated');

            return $updatedMember->load('membershipPlan');
        });
    }

    public function delete(Member $member): void
    {
        $this->transaction(function () use ($member): void {
            $this->rfidCardService->disableAllForMember($member);

            $this->members->update($member, [
                'phone' => $member->phone.'-deleted-'.$member->id,
                'rfid_card' => null,
            ]);

            $this->activityLogger->log('member.deleted', $member, 'Member deleted', [
                'member_code' => $member->member_code,
            ]);

            $this->members->delete($member);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPlanExpiry(array $data): array
    {
        if (empty($data['membership_plan_id']) || filled($data['membership_expires_at'] ?? null)) {
            return $data;
        }

        $plan = MembershipPlan::query()->find($data['membership_plan_id']);

        if ($plan === null) {
            return $data;
        }

        $joinedAt = $data['joined_at'] ?? now()->toDateString();
        $data['membership_expires_at'] = Carbon::parse($joinedAt)
            ->addDays($plan->duration_days)
            ->toDateString();

        return $data;
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $this->photoStorage->store($photo);
    }

    private function deletePhoto(?string $path): void
    {
        $this->photoStorage->delete($path);
    }
}
