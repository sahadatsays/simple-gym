<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\LockerReservationRepositoryInterface;
use App\Enums\LockerStatus;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexLockerRenewalRequest;
use App\Http\Requests\Admin\IndexLockerReservationRequest;
use App\Http\Requests\Admin\RenewLockerReservationRequest;
use App\Http\Requests\Admin\StoreLockerReservationRequest;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use App\Services\GymSettingService;
use App\Services\LockerReservationService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class LockerReservationController extends Controller
{
    public function __construct(
        private LockerReservationRepositoryInterface $reservations,
        private LockerReservationService $reservationService,
        private GymSettingService $gymSettings,
    ) {}

    public function index(IndexLockerReservationRequest $request): View
    {
        $this->reservationService->expireDueReservations();

        $filters = $request->validated();

        return view('admin.locker-reservations.index', [
            'reservations' => $this->reservations->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'filters' => $filters,
        ]);
    }

    public function renewals(IndexLockerRenewalRequest $request): View
    {
        $this->reservationService->expireDueReservations();

        $filters = $request->validated();

        return view('admin.locker-reservations.renewals', [
            'reservations' => $this->reservations->paginateRenewalReview(
                $filters,
                $this->gymSettings->membershipReminderDays(),
                config('gym.pagination.per_page'),
            ),
            'filters' => $filters,
            'reminderDays' => $this->gymSettings->membershipReminderDays(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LockerReservation::class);

        $this->reservationService->expireDueReservations();

        return view('admin.locker-reservations.create', [
            'members' => Member::query()->orderBy('name')->get(['id', 'name', 'member_code']),
            'lockers' => Locker::query()
                ->whereIn('status', [LockerStatus::Available, LockerStatus::Reserved])
                ->orderBy('locker_number')
                ->get(['id', 'locker_number', 'location', 'monthly_fee', 'status']),
            'selectedLockerId' => request()->integer('locker_id') ?: null,
            'selectedMemberId' => request()->integer('member_id') ?: null,
        ]);
    }

    public function store(StoreLockerReservationRequest $request): RedirectResponse
    {
        try {
            $reservation = $this->reservationService->reserve(
                $request->locker(),
                $request->member(),
                $request->validated('start_month'),
                $request->validated('payment_method'),
                $request->user()?->id,
            );
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['reservation' => $exception->getMessage()]);
        }

        Flash::success('Locker reservation activated.');

        return redirect()->route('admin.locker-reservations.show', $reservation);
    }

    public function show(LockerReservation $lockerReservation): View
    {
        $this->authorize('view', $lockerReservation);

        $lockerReservation->load(['locker', 'member', 'invoice', 'creator']);

        return view('admin.locker-reservations.show', [
            'reservation' => $lockerReservation,
            'nextPeriod' => $this->reservationService->nextPeriod($lockerReservation),
        ]);
    }

    public function renew(RenewLockerReservationRequest $request, LockerReservation $lockerReservation): RedirectResponse
    {
        try {
            $renewal = $this->reservationService->renew(
                $lockerReservation,
                $request->validated('payment_method'),
                $request->user()?->id,
            );
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['reservation' => $exception->getMessage()]);
        }

        Flash::success('Locker reservation renewed.');

        return redirect()->route('admin.locker-reservations.show', $renewal);
    }

    public function cancel(LockerReservation $lockerReservation): RedirectResponse
    {
        $this->authorize('cancel', $lockerReservation);

        try {
            $this->reservationService->cancel($lockerReservation);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('Locker reservation cancelled.');

        return redirect()->route('admin.locker-reservations.show', $lockerReservation);
    }
}
