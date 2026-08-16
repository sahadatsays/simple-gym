<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexExpenseCategoryRequest;
use App\Http\Requests\Admin\StoreExpenseCategoryRequest;
use App\Http\Requests\Admin\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private ExpenseCategoryRepositoryInterface $categories,
        private ExpenseCategoryService $expenseCategoryService,
    ) {}

    public function index(IndexExpenseCategoryRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.expense-categories.index', [
            'categories' => $this->categories->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ExpenseCategory::class);

        return view('admin.expense-categories.create');
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $this->expenseCategoryService->create(
            data: $request->validated(),
            createdBy: $request->user()?->id,
        );

        Flash::success('Expense category created successfully.');

        return redirect()->route('admin.expense-categories.index');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        $this->authorize('update', $expenseCategory);

        return view('admin.expense-categories.edit', [
            'category' => $expenseCategory,
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('update', $expenseCategory);

        $this->expenseCategoryService->update($expenseCategory, $request->validated());

        Flash::success('Expense category updated successfully.');

        return redirect()->route('admin.expense-categories.index');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->authorize('delete', $expenseCategory);

        try {
            $this->expenseCategoryService->delete($expenseCategory);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        Flash::success('Expense category deleted successfully.');

        return redirect()->route('admin.expense-categories.index');
    }
}
