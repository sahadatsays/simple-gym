<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\BorrowingRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBorrowingRepaymentRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use App\Support\Flash;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class BorrowingRepaymentController extends Controller
{
    public function __construct(
        private BorrowingRepositoryInterface $borrowings,
        private BorrowingService $borrowingService,
    ) {}

    public function create(): View
    {
        abort_unless(request()->user()?->can('borrowings.edit') ?? false, 403);

        $selectedBorrowingId = request()->integer('borrowing_id');

        return view('admin.borrowings.repayments.create', [
            'borrowings' => $this->borrowings->repayable(),
            'selectedBorrowingId' => $selectedBorrowingId > 0 ? $selectedBorrowingId : null,
        ]);
    }

    public function confirm(StoreBorrowingRepaymentRequest $request): View
    {
        $data = $request->validated();
        $borrowing = Borrowing::query()->findOrFail($data['borrowing_id']);
        $amount = Money::round((float) $data['amount']);
        $remainingAfter = Money::round($borrowing->remaining_amount - $amount);

        return view('admin.borrowings.repayments.confirm', [
            'borrowing' => $borrowing,
            'data' => $data,
            'amount' => $amount,
            'remainingAfter' => $remainingAfter,
            'fullyRepaid' => ! Money::greaterThan($remainingAfter, 0),
        ]);
    }

    public function store(StoreBorrowingRepaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $borrowing = Borrowing::query()->findOrFail($data['borrowing_id']);

        try {
            $repayment = $this->borrowingService->recordRepayment(
                borrowing: $borrowing,
                data: $data,
                createdBy: $request->user()?->id,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.borrowings.repayments.create', ['borrowing_id' => $borrowing->id])
                ->withErrors(['amount' => $exception->getMessage()])
                ->withInput();
        }

        Flash::success('Repayment recorded successfully.');

        return redirect()->route('admin.borrowings.show', $repayment->borrowing_id);
    }
}
