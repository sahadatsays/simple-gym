<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\MembershipPlanRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexMembershipPlanRequest;
use App\Http\Requests\Admin\StoreMembershipPlanRequest;
use App\Http\Requests\Admin\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Services\GymSettingService;
use App\Services\MembershipPlanService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class MembershipPlanController extends Controller
{
    public function __construct(
        private MembershipPlanRepositoryInterface $plans,
        private MembershipPlanService $membershipPlanService,
    ) {}

    public function index(IndexMembershipPlanRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.membership-plans.index', [
            'plans' => $this->plans->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MembershipPlan::class);

        return view('admin.membership-plans.create', [
            'defaultAdmissionFee' => app(GymSettingService::class)->get()->default_admission_fee,
        ]);
    }

    public function store(StoreMembershipPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', MembershipPlan::class);

        $validated = $request->validated();
        $validated['features'] = $this->membershipPlanService->normalizeFeatures($validated['features'] ?? null);

        $this->membershipPlanService->create($validated);

        Flash::success('Membership plan created successfully.');

        return redirect()->route('admin.membership-plans.index');
    }

    public function edit(MembershipPlan $membershipPlan): View
    {
        $this->authorize('update', $membershipPlan);

        return view('admin.membership-plans.edit', [
            'plan' => $membershipPlan,
        ]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->authorize('update', $membershipPlan);

        $validated = $request->validated();
        $validated['features'] = $this->membershipPlanService->normalizeFeatures($validated['features'] ?? null);

        $this->membershipPlanService->update($membershipPlan, $validated);

        Flash::success('Membership plan updated successfully.');

        return redirect()->route('admin.membership-plans.index');
    }

    public function destroy(MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->authorize('delete', $membershipPlan);

        try {
            $this->membershipPlanService->delete($membershipPlan);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Membership plan deleted successfully.');

        return redirect()->route('admin.membership-plans.index');
    }
}
