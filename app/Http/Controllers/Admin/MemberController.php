<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexMemberRequest;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\MemberService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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

    public function create(): View
    {
        $this->authorize('create', Member::class);

        return view('admin.members.create', [
            'plans' => MembershipPlan::query()->orderBy('name')->get(['id', 'name', 'duration_days']),
            'nextMemberCode' => $this->members->nextMemberCode(),
        ]);
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

        return view('admin.members.show', [
            'member' => $member->load(['membershipPlan', 'payments' => fn ($query) => $query->latest()->limit(10)]),
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('admin.members.edit', [
            'member' => $member->load('membershipPlan'),
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

        $this->memberService->delete($member);

        Flash::success('Member deleted successfully.');

        return redirect()->route('admin.members.index');
    }
}
