<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexMemberRequest;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\MemberService;
use App\Support\Flash;
use App\Support\MemberTransactionSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class MemberController extends Controller
{
    public function __construct(
        private MemberRepositoryInterface $members,
        private MemberService $memberService,
    ) {}

    public function index(IndexMemberRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.members.index', [
            'members' => $this->members->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'plans' => MembershipPlan::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(): RedirectResponse|View
    {
        $this->authorize('create', Member::class);

        return redirect()->route('admin.members.register.create');
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $member = $this->memberService->create(
            $request->validated(),
            $request->file('photo'),
        );

        Flash::success('Member created successfully.');

        return redirect()->route('admin.members.show', $member);
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load([
            'membershipPlan',
            'activeRfidCard',
            'payments' => fn ($query) => $query->with('invoice')->latest('paid_at'),
            'invoices' => fn ($query) => $query
                ->where('type', InvoiceType::PosSale)
                ->with('payments')
                ->latest('issued_at'),
            'membershipRenewals' => fn ($query) => $query->with(['membershipPlan', 'invoice.payment'])->latest('renewed_at')->limit(10),
        ]);

        return view('admin.members.show', [
            'member' => $member,
            'transactionSummary' => MemberTransactionSummary::forMember($member),
            'membershipPayments' => $member->payments
                ->whereIn('type', [PaymentType::AdmissionFee, PaymentType::MembershipFee])
                ->values(),
            'posPayments' => $member->payments
                ->where('type', PaymentType::PosSale)
                ->values(),
            'posOrders' => $member->invoices,
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('admin.members.edit', [
            'member' => $member->load(['membershipPlan', 'activeRfidCard']),
            'plans' => MembershipPlan::query()->orderBy('name')->get(['id', 'name', 'duration_days']),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $this->memberService->update(
            $member,
            [
                ...$request->validated(),
                'remove_photo' => $request->boolean('remove_photo'),
            ],
            $request->file('photo'),
        );

        Flash::success('Member updated successfully.');

        return redirect()->route('admin.members.show', $member);
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        try {
            $this->memberService->delete($member);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Member deleted successfully.');

        return redirect()->route('admin.members.index');
    }
}
