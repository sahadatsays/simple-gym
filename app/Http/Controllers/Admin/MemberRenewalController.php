<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Enums\PlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRenewalRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\MembershipRenewalService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberRenewalController extends Controller
{
    public function __construct(
        private MembershipRenewalService $renewalService,
    ) {}

    public function create(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $search = $request->string('search')->trim()->toString();

        $members = Member::query()
            ->with('membershipPlan')
            ->where('status', '!=', MemberStatus::Pending)
            ->search($search !== '' ? $search : null)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'member_code', 'phone', 'membership_expires_at', 'status', 'membership_plan_id']);

        return view('admin.members.renew.search', [
            'members' => $members,
            'search' => $search,
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
