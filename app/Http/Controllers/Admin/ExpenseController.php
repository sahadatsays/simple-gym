<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexExpenseRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseRepositoryInterface $expenses,
        private ExpenseService $expenseService,
    ) {}

    public function index(IndexExpenseRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.expenses.index', [
            'expenses' => $this->expenses->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
            'categories' => ExpenseCategory::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('admin.expenses.create', [
            'categories' => ExpenseCategory::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $expense = $this->expenseService->create(
            data: collect($validated)->except('attachment')->all(),
            attachment: $request->file('attachment'),
            createdBy: $request->user()?->id,
        );

        Flash::success('Expense created successfully.');

        return redirect()->route('admin.expenses.show', $expense);
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);

        $expense->load(['category', 'creator']);

        return view('admin.expenses.show', [
            'expense' => $expense,
        ]);
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('admin.expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::query()
                ->where(function ($query) use ($expense): void {
                    $query->active()->orWhere('id', $expense->expense_category_id);
                })
                ->ordered()
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validated();

        $this->expenseService->update(
            expense: $expense,
            data: collect($validated)->except(['attachment', 'remove_attachment'])->all(),
            attachment: $request->file('attachment'),
            removeAttachment: (bool) ($validated['remove_attachment'] ?? false),
        );

        Flash::success('Expense updated successfully.');

        return redirect()->route('admin.expenses.show', $expense);
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        Flash::success('Expense deleted successfully.');

        return redirect()->route('admin.expenses.index');
    }
}
