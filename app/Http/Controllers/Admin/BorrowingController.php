<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\BorrowingRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexBorrowingRequest;
use App\Http\Requests\Admin\StoreBorrowingRequest;
use App\Http\Requests\Admin\UpdateBorrowingRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class BorrowingController extends Controller
{
    public function __construct(
        private BorrowingRepositoryInterface $borrowings,
        private BorrowingService $borrowingService,
    ) {}

    public function index(IndexBorrowingRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.borrowings.index', [
            'borrowings' => $this->borrowings->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Borrowing::class);

        return view('admin.borrowings.create');
    }

    public function store(StoreBorrowingRequest $request): RedirectResponse
    {
        $borrowing = $this->borrowingService->create(
            data: $request->validated(),
            createdBy: $request->user()?->id,
        );

        Flash::success('Borrowing created successfully.');

        return redirect()->route('admin.borrowings.show', $borrowing);
    }

    public function show(Borrowing $borrowing): View
    {
        $this->authorize('view', $borrowing);

        $borrowing->load([
            'creator',
            'repayments' => fn ($query) => $query->orderBy('repayment_date')->orderBy('id'),
        ]);

        return view('admin.borrowings.show', [
            'borrowing' => $borrowing,
        ]);
    }

    public function edit(Borrowing $borrowing): View
    {
        $this->authorize('update', $borrowing);

        return view('admin.borrowings.edit', [
            'borrowing' => $borrowing,
        ]);
    }

    public function update(UpdateBorrowingRequest $request, Borrowing $borrowing): RedirectResponse
    {
        try {
            $this->borrowingService->update($borrowing, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()])->withInput();
        }

        Flash::success('Borrowing updated successfully.');

        return redirect()->route('admin.borrowings.show', $borrowing);
    }

    public function destroy(Borrowing $borrowing): RedirectResponse
    {
        $this->authorize('delete', $borrowing);

        try {
            $this->borrowingService->delete($borrowing);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['borrowing' => $exception->getMessage()]);
        }

        Flash::success('Borrowing deleted successfully.');

        return redirect()->route('admin.borrowings.index');
    }
}
