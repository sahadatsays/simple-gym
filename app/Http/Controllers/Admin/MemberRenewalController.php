<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRenewalReviewRequest;
use App\Http\Requests\Admin\StoreMemberRenewalRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\GymSettingService;
use App\Services\MembershipRenewalService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberRenewalController extends Controller
{
    public function __construct(
        private MembershipRenewalService $renewalService,
        private GymSettingService $gymSettings,
    ) {}

    public function create(IndexRenewalReviewRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.members.renew.search', [
            'members' => $this->renewalService->paginateReview($filters),
            'filters' => $filters,
            'reminderDays' => $this->gymSettings->membershipReminderDays(),
            'perPageOptions' => config('gym.pagination.per_page_options', [15]),
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('renew', $member);

        abort_unless($member->isRenewable(), 404);

        $member->load('membershipPlan');

        $plans = MembershipPlan::query()
            ->where('status', PlanStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_days', 'membership_fee']);

        $newExpiryPreview = $member->membership_plan_id
            ? $this->renewalService->previewExpiry($member, $member->membershipPlan)
            : null;

        return view('admin.members.renew.form', [
            'member' => $member,
            'plans' => $plans,
            'newExpiryPreview' => $newExpiryPreview,
            'renewals' => $member->membershipRenewals()
                ->with(['membershipPlan', 'invoice.payment'])
                ->latest('renewed_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function store(StoreMemberRenewalRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('renew', $member);

        try {
            $result = $this->renewalService->renew($member, $request->validated());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['renewal' => $exception->getMessage()]);
        }

        Flash::success('Membership renewed successfully. Receipt generated.');

        return redirect()->route('admin.invoices.show', $result->invoice);
    }
}
