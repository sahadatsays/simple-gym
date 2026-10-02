<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\RfidCardRepositoryInterface;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRfidCardRequest;
use App\Http\Requests\Admin\IndexRfidCardRequest;
use App\Http\Requests\Admin\ReplaceRfidCardRequest;
use App\Http\Requests\Admin\StoreRfidCardRequest;
use App\Models\Member;
use App\Models\RfidCard;
use App\Services\GymSettingService;
use App\Services\RfidCardService;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class RfidCardController extends Controller
{
    public function __construct(
        private RfidCardRepositoryInterface $rfidCards,
        private RfidCardService $rfidCardService,
        private GymSettingService $gymSettings,
    ) {}

    public function index(IndexRfidCardRequest $request): View
    {
        $filters = $request->validated();

        $settings = $this->gymSettings->get();

        return view('admin.rfid-cards.index', [
            'cards' => $this->rfidCards->paginateWithFilters($filters, config('gym.pagination.per_page')),
            'members' => Member::query()->orderBy('name')->get(['id', 'name', 'member_code']),
            'filters' => $filters,
            'cardFee' => (float) $settings->rfid_card_fee,
            'cardDeposit' => (float) $settings->rfid_card_deposit,
            'replacementFee' => (float) $settings->rfid_replacement_card_fee,
        ]);
    }

    public function store(StoreRfidCardRequest $request): RedirectResponse
    {
        $this->authorize('create', RfidCard::class);

        $this->rfidCardService->register(
            $request->validated('card_number'),
            $request->user()?->id,
        );

        Flash::success('RFID card registered successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function show(RfidCard $rfidCard): View
    {
        $this->authorize('view', $rfidCard);

        $rfidCard->load([
            'creator',
            'member',
            'assignments.member',
            'assignments.invoice',
        ]);

        return view('admin.rfid-cards.show', [
            'card' => $rfidCard,
        ]);
    }

    public function assign(AssignRfidCardRequest $request, RfidCard $rfidCard): RedirectResponse
    {
        $this->authorize('assign', $rfidCard);

        try {
            $this->rfidCardService->assign(
                $rfidCard,
                $request->member(),
                $request->validated('payment_method'),
            );
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card assigned successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function replace(ReplaceRfidCardRequest $request): RedirectResponse
    {
        $this->authorize('replace', RfidCard::class);

        try {
            $this->rfidCardService->replace(
                $request->member(),
                $request->validated('card_number'),
                $request->validated('payment_method'),
                $request->user()?->id,
            );
        } catch (PaymentFailedException|InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card replaced successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function disable(RfidCard $rfidCard): RedirectResponse
    {
        $this->authorize('disable', $rfidCard);

        try {
            $this->rfidCardService->disable($rfidCard);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card disabled successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function enable(RfidCard $rfidCard): RedirectResponse
    {
        $this->authorize('enable', $rfidCard);

        try {
            $this->rfidCardService->enable($rfidCard);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card enabled successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function returnCard(RfidCard $rfidCard): RedirectResponse
    {
        $this->authorize('returnCard', $rfidCard);

        try {
            $this->rfidCardService->returnCard($rfidCard);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card returned successfully.');

        return redirect()->route('admin.rfid-cards.index');
    }

    public function markLost(RfidCard $rfidCard): RedirectResponse
    {
        $this->authorize('markLost', $rfidCard);

        try {
            $this->rfidCardService->markLost($rfidCard);
        } catch (InvalidArgumentException $exception) {
            Flash::error($exception->getMessage());

            return back();
        }

        Flash::success('RFID card marked as lost.');

        return redirect()->route('admin.rfid-cards.index');
    }
}
