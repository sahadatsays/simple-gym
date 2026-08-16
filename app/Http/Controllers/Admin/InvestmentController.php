<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\InvestmentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexInvestmentRequest;
use App\Http\Requests\Admin\StoreInvestmentRequest;
use App\Http\Requests\Admin\UpdateInvestmentRequest;
use App\Models\Investment;
use App\Models\InvestmentCategory;
use App\Services\InvestmentService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function __construct(
        private InvestmentRepositoryInterface $investments,
        private InvestmentService $investmentService,
    ) {}

    public function index(IndexInvestmentRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.investments.index', [
            'investments' => $this->investments->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'categories' => InvestmentCategory::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Investment::class);

        return view('admin.investments.create', [
            'categories' => InvestmentCategory::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreInvestmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $investment = $this->investmentService->create(
            data: collect($validated)->except('attachment')->all(),
            attachment: $request->file('attachment'),
            createdBy: $request->user()?->id,
        );

        Flash::success('Investment created successfully.');

        return redirect()->route('admin.investments.show', $investment);
    }

    public function show(Investment $investment): View
    {
        $this->authorize('view', $investment);

        $investment->load(['category', 'creator']);

        return view('admin.investments.show', [
            'investment' => $investment,
        ]);
    }

    public function edit(Investment $investment): View
    {
        $this->authorize('update', $investment);

        return view('admin.investments.edit', [
            'investment' => $investment,
            'categories' => InvestmentCategory::query()
                ->where(function ($query) use ($investment): void {
                    $query->active()->orWhere('id', $investment->investment_category_id);
                })
                ->ordered()
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateInvestmentRequest $request, Investment $investment): RedirectResponse
    {
        $validated = $request->validated();

        $this->investmentService->update(
            investment: $investment,
            data: collect($validated)->except(['attachment', 'remove_attachment'])->all(),
            attachment: $request->file('attachment'),
            removeAttachment: (bool) ($validated['remove_attachment'] ?? false),
        );

        Flash::success('Investment updated successfully.');

        return redirect()->route('admin.investments.show', $investment);
    }

    public function destroy(Investment $investment): RedirectResponse
    {
        $this->authorize('delete', $investment);

        $this->investmentService->delete($investment);

        Flash::success('Investment deleted successfully.');

        return redirect()->route('admin.investments.index');
    }
}
