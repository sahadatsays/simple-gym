<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\InvestmentCategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexInvestmentCategoryRequest;
use App\Http\Requests\Admin\StoreInvestmentCategoryRequest;
use App\Http\Requests\Admin\UpdateInvestmentCategoryRequest;
use App\Models\InvestmentCategory;
use App\Services\InvestmentCategoryService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class InvestmentCategoryController extends Controller
{
    public function __construct(
        private InvestmentCategoryRepositoryInterface $categories,
        private InvestmentCategoryService $investmentCategoryService,
    ) {}

    public function index(IndexInvestmentCategoryRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.investment-categories.index', [
            'categories' => $this->categories->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', InvestmentCategory::class);

        return view('admin.investment-categories.create');
    }

    public function store(StoreInvestmentCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', InvestmentCategory::class);

        $this->investmentCategoryService->create($request->validated());

        Flash::success('Investment category created successfully.');

        return redirect()->route('admin.investment-categories.index');
    }

    public function edit(InvestmentCategory $investmentCategory): View
    {
        $this->authorize('update', $investmentCategory);

        return view('admin.investment-categories.edit', [
            'category' => $investmentCategory,
        ]);
    }

    public function update(UpdateInvestmentCategoryRequest $request, InvestmentCategory $investmentCategory): RedirectResponse
    {
        $this->authorize('update', $investmentCategory);

        $this->investmentCategoryService->update($investmentCategory, $request->validated());

        Flash::success('Investment category updated successfully.');

        return redirect()->route('admin.investment-categories.index');
    }

    public function destroy(InvestmentCategory $investmentCategory): RedirectResponse
    {
        $this->authorize('delete', $investmentCategory);

        try {
            $this->investmentCategoryService->delete($investmentCategory);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        Flash::success('Investment category deleted successfully.');

        return redirect()->route('admin.investment-categories.index');
    }
}
