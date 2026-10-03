<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\LockerRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexLockerRequest;
use App\Http\Requests\Admin\StoreLockerRequest;
use App\Http\Requests\Admin\UpdateLockerRequest;
use App\Models\Locker;
use App\Services\LockerReservationService;
use App\Services\LockerService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class LockerController extends Controller
{
    public function __construct(
        private LockerRepositoryInterface $lockers,
        private LockerService $lockerService,
        private LockerReservationService $reservationService,
    ) {}

    public function index(IndexLockerRequest $request): View
    {
        $this->reservationService->expireDueReservations();

        $filters = $request->validated();

        return view('admin.lockers.index', [
            'lockers' => $this->lockers->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Locker::class);

        return view('admin.lockers.create');
    }

    public function store(StoreLockerRequest $request): RedirectResponse
    {
        try {
            $locker = $this->lockerService->create(
                $request->validated(),
                $request->user()?->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['locker_number' => $exception->getMessage()]);
        }

        Flash::success('Locker created successfully.');

        return redirect()->route('admin.lockers.show', $locker);
    }

    public function show(Locker $locker): View
    {
        $this->authorize('view', $locker);

        $this->reservationService->expireDueReservations();

        $locker->load(['creator', 'reservations.member', 'reservations.invoice']);

        return view('admin.lockers.show', [
            'locker' => $locker,
        ]);
    }

    public function edit(Locker $locker): View
    {
        $this->authorize('update', $locker);

        return view('admin.lockers.edit', [
            'locker' => $locker,
        ]);
    }

    public function update(UpdateLockerRequest $request, Locker $locker): RedirectResponse
    {
        try {
            $this->lockerService->update($locker, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['status' => $exception->getMessage()]);
        }

        Flash::success('Locker updated successfully.');

        return redirect()->route('admin.lockers.show', $locker);
    }

    public function destroy(Locker $locker): RedirectResponse
    {
        $this->authorize('delete', $locker);

        try {
            $this->lockerService->delete($locker);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Locker deleted successfully.');

        return redirect()->route('admin.lockers.index');
    }
}
