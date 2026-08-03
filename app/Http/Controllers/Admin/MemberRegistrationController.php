<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Enums\PlanStatus;
use App\Enums\RfidCardStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRegistrationRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\RfidCard;
use App\Services\MemberRegistrationService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberRegistrationController extends Controller
{
    public function __construct(
        private MemberRepositoryInterface $members,
        private MemberRegistrationService $registrationService,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Member::class);

        $plans = MembershipPlan::query()
            ->where('status', PlanStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_days', 'admission_fee', 'membership_fee']);

        return view('admin.members.register', [
            'plans' => $plans,
            'nextMemberCode' => $this->members->nextMemberCode(),
            'unassignedCards' => RfidCard::query()
                ->where('status', RfidCardStatus::Unassigned)
                ->orderBy('card_number')
                ->get(['id', 'card_number']),
        ]);
    }

    public function store(StoreMemberRegistrationRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $result = $this->registrationService->register(
            $request->validated(),
            $request->file('photo'),
        );

        Flash::success('Member registered successfully. Membership activated and receipt generated.');

        return redirect()->route('admin.members.receipt', [
            'member' => $result->member,
            'invoice' => $result->invoice,
        ]);
    }

    public function receipt(Member $member, Invoice $invoice): View
    {
        $this->authorize('view', $member);

        abort_unless($invoice->member_id === $member->id, 404);

        $invoice->load(['membershipPlan', 'payment']);
        $member->load(['membershipPlan', 'activeRfidCard']);

        return view('admin.members.receipt', [
            'member' => $member,
            'invoice' => $invoice,
            'payment' => $invoice->payment,
        ]);
    }
}
