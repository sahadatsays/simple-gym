@php
    $activeCard = $member->activeRfidCard;
    $issueDate = $activeCard?->openAssignment?->issue_date ?? $activeCard?->assigned_at;
    $cardDeposit = $cardDeposit ?? 0;
    $replacementFee = $replacementFee ?? 0;
    $memberHasPaidCardFee = $member->hasPaidRfidCardFee();
    $cardFeeDue = $cardFee > 0 && ! $memberHasPaidCardFee;
    $depositDue = $cardDeposit > 0 && ! $member->hasPaidRfidDeposit();
    $issueChargeDue = $cardFeeDue || $depositDue;
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h3 class="h6 fw-semibold mb-0">RFID Card</h3>
            <div class="d-flex flex-wrap gap-2">
                @if ($activeCard)
                    @if ($issueChargeDue && $activeCard->openAssignment?->invoice_id === null)
                        @can('assign', $activeCard)
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#memberCollectCardFeeModal">
                                Collect fee
                            </button>
                        @endcan
                    @endif
                    @can('replace', App\Models\RfidCard::class)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#memberReplaceCardModal">
                            Replace
                        </button>
                    @endcan
                    @can('returnCard', $activeCard)
                        <form
                            action="{{ route('admin.rfid-cards.return', $activeCard) }}"
                            method="POST"
                            onsubmit="return confirm('Return this RFID card and close the assignment?');"
                        >
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-light">Return card</button>
                        </form>
                    @endcan
                @else
                    @can('issue', App\Models\RfidCard::class)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#memberIssueCardModal">
                            Issue card
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        @if ($activeCard)
            <dl class="row sg-profile-list mb-0">
                <dt class="col-sm-4">Card number</dt>
                <dd class="col-sm-8">
                    @can('view', $activeCard)
                        <a href="{{ route('admin.rfid-cards.show', $activeCard) }}">{{ $activeCard->card_number }}</a>
                    @else
                        {{ $activeCard->card_number }}
                    @endcan
                </dd>

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">
                    <span class="sg-status-badge {{ $activeCard->status->badgeClass() }}">{{ $activeCard->status->label() }}</span>
                </dd>

                <dt class="col-sm-4">Issue date</dt>
                <dd class="col-sm-8">{{ $issueDate?->format('M j, Y') ?? '—' }}</dd>
            </dl>
        @else
            <p class="text-muted small mb-0">No active RFID card.</p>
        @endif
    </div>

    <div class="table-responsive border-top">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Card history</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Issue date</th>
                    <th class="d-none d-lg-table-cell pe-4">Return date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($member->rfidCardAssignments as $assignment)
                    <tr>
                        <td class="ps-4">
                            <div>{{ $assignment->rfidCard?->card_number ?? '—' }}</div>
                            <div class="small text-muted d-md-none">
                                {{ $assignment->issue_date?->format('M j, Y') ?? '—' }}
                                @if ($assignment->return_date)
                                    – {{ $assignment->return_date->format('M j, Y') }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="sg-status-badge {{ $assignment->status->badgeClass() }}">{{ $assignment->status->label() }}</span>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $assignment->issue_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell pe-4">{{ $assignment->return_date?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No card history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="px-4 py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h6 fw-semibold mb-0">Locker</h3>
            @can('create', App\Models\LockerReservation::class)
                <a href="{{ route('admin.locker-reservations.create', ['member_id' => $member->id]) }}" class="btn btn-sm btn-light">
                    Reserve locker
                </a>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Locker number</th>
                        <th>Status</th>
                        <th class="d-none d-md-table-cell">Start</th>
                        <th class="d-none d-lg-table-cell">End</th>
                        <th>Monthly fee</th>
                        <th class="text-end pe-4">Renewal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($member->lockerReservations as $reservation)
                        <tr>
                            <td class="ps-4">
                                @if ($reservation->locker)
                                    @can('view', $reservation->locker)
                                        <a href="{{ route('admin.lockers.show', $reservation->locker) }}">{{ $reservation->locker->locker_number }}</a>
                                    @else
                                        {{ $reservation->locker->locker_number }}
                                    @endcan
                                @else
                                    —
                                @endif
                                <div class="small text-muted d-md-none">
                                    {{ $reservation->start_date->format('M j, Y') }} – {{ $reservation->end_date->format('M j, Y') }}
                                </div>
                            </td>
                            <td>
                                <span class="sg-status-badge {{ $reservation->status->badgeClass() }}">{{ $reservation->status->label() }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $reservation->start_date->format('M j, Y') }}</td>
                            <td class="d-none d-lg-table-cell">{{ $reservation->end_date->format('M j, Y') }}</td>
                            <td>{{ App\Support\MoneyFormatter::format($reservation->monthly_fee, $gymCurrency) }}</td>
                            <td class="text-end pe-4">
                                @if ($reservation->status !== App\Enums\LockerReservationStatus::Cancelled && $reservation->locker?->canBeReserved())
                                    @can('renew', $reservation)
                                        <form action="{{ route('admin.locker-reservations.renew', $reservation) }}" method="POST" class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                                            @csrf
                                            @if ((float) ($reservation->locker->monthly_fee ?? 0) > 0)
                                                <select name="payment_method" class="form-select form-select-sm" style="max-width: 9rem;" required>
                                                    @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                                                        <option value="{{ $value }}" @selected($value === 'cash')>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <button type="submit" class="btn btn-sm btn-primary">Renew</button>
                                        </form>
                                    @endcan
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No locker reservation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('issue', App\Models\RfidCard::class)
    @unless ($activeCard)
        <div class="modal fade" id="memberIssueCardModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.issue') }}" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" value="{{ $member->id }}">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Issue RFID card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                RFID Card {{ App\Support\MoneyFormatter::format($cardFee, $gymCurrency) }}, RFID Deposit {{ App\Support\MoneyFormatter::format($cardDeposit, $gymCurrency) }}.
                            </p>
                            <x-forms.input
                                label="Card number"
                                name="card_number"
                                placeholder="Scan or enter RFID"
                                required
                            />
                            @if ($issueChargeDue)
                                <x-forms.select
                                    label="Payment method"
                                    name="payment_method"
                                    :options="App\Enums\PaymentMethod::options()"
                                    selected="cash"
                                    required
                                />
                            @endif
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Issue card</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endunless

    @if ($activeCard)
        @if ($issueChargeDue && $activeCard->openAssignment?->invoice_id === null)
            @can('assign', $activeCard)
                <div class="modal fade" id="memberCollectCardFeeModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <form action="{{ route('admin.rfid-cards.collect-fee', $activeCard) }}" method="POST">
                                @csrf
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold">Collect card fee</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">
                                        @if ($cardFeeDue)
                                            RFID Card {{ App\Support\MoneyFormatter::format($cardFee, $gymCurrency) }}.
                                        @endif
                                        @if ($depositDue)
                                            RFID Deposit {{ App\Support\MoneyFormatter::format($cardDeposit, $gymCurrency) }}.
                                        @endif
                                    </p>
                                    <x-forms.select
                                        label="Payment method"
                                        name="payment_method"
                                        :options="App\Enums\PaymentMethod::options()"
                                        selected="cash"
                                        required
                                    />
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Receive payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif
        <div class="modal fade" id="memberReplaceCardModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.replace') }}" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" value="{{ $member->id }}">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Replace RFID card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                The current assignment stays in history.
                                RFID Replacement {{ App\Support\MoneyFormatter::format($replacementFee, $gymCurrency) }}.
                            </p>
                            <x-forms.input
                                label="New card number"
                                name="card_number"
                                placeholder="Scan or enter new RFID"
                                required
                            />
                            @if ($replacementFee > 0)
                                <x-forms.select
                                    label="Payment method"
                                    name="payment_method"
                                    :options="App\Enums\PaymentMethod::options()"
                                    selected="cash"
                                    required
                                />
                            @endif
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Replace</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan
