@props(['card', 'members', 'cardFee' => 0, 'cardDeposit' => 0, 'replacementFee' => 0, 'gymCurrency' => 'BDT'])

<div class="dropdown d-inline-block">
    <button
        class="btn btn-sm btn-light"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        data-bs-popper-config='{"strategy":"fixed"}'
        aria-expanded="false"
    >
        Actions
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item" href="{{ route('admin.rfid-cards.show', $card) }}">Assignment History</a>
        </li>

        @if ($card->isAssignable())
            @can('assign', $card)
                <li>
                    <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#assignCardModal-{{ $card->id }}"
                    >
                        Assign Card
                    </button>
                </li>
            @endcan
        @endif

        @if ($card->isActive())
            @can('replace', App\Models\RfidCard::class)
                <li>
                    <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#replaceCardModal-{{ $card->id }}"
                    >
                        Replace Card
                    </button>
                </li>
            @endcan

            @can('returnCard', $card)
                <li>
                    <form
                        action="{{ route('admin.rfid-cards.return', $card) }}"
                        method="POST"
                        onsubmit="return confirm('Return this RFID card and close the assignment?');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">Return Card</button>
                    </form>
                </li>
            @endcan

            @can('markLost', $card)
                <li>
                    <form
                        action="{{ route('admin.rfid-cards.lost', $card) }}"
                        method="POST"
                        onsubmit="return confirm('Mark this RFID card as lost? It cannot be assigned again.');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item text-danger">Mark Lost</button>
                    </form>
                </li>
            @endcan

            @can('disable', $card)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form
                        action="{{ route('admin.rfid-cards.disable', $card) }}"
                        method="POST"
                        onsubmit="return confirm('Disable this RFID card? The member will be removed from all access devices.');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item text-danger">Disable Card</button>
                    </form>
                </li>
            @endcan
        @endif

        @if ($card->canBeEnabled())
            @can('enable', $card)
                <li>
                    <form
                        action="{{ route('admin.rfid-cards.enable', $card) }}"
                        method="POST"
                        onsubmit="return confirm('Enable this RFID card and restore device access?');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item text-success">Enable Card</button>
                    </form>
                </li>
            @endcan
        @endif
    </ul>
</div>

@if ($card->isAssignable())
    @can('assign', $card)
        <div class="modal fade" id="assignCardModal-{{ $card->id }}" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.assign', $card) }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Assign RFID Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Assign <strong>{{ $card->card_number }}</strong> to a member. The member's current card assignment will be closed.
                                Card fee {{ App\Support\MoneyFormatter::format($cardFee, $gymCurrency) }}, deposit {{ App\Support\MoneyFormatter::format($cardDeposit, $gymCurrency) }}.
                            </p>
                            <x-forms.searchable-select
                                label="Member"
                                name="member_id"
                                id="assign-member-{{ $card->id }}"
                                :options="$members->mapWithKeys(fn ($member) => [$member->id => $member->name.' ('.$member->member_code.')'])->all()"
                                placeholder="Search member..."
                                required
                            />
                            @if (($cardFee + $cardDeposit) > 0)
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
                            <button type="submit" class="btn btn-primary">Assign Card</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif

@if ($card->isActive() && $card->member)
    @can('replace', App\Models\RfidCard::class)
        <div class="modal fade" id="replaceCardModal-{{ $card->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.replace') }}" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" value="{{ $card->member_id }}">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Replace RFID Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Replace the active card for <strong>{{ $card->member->name }}</strong>. The current assignment will be closed and kept in history.
                                Replacement fee {{ App\Support\MoneyFormatter::format($replacementFee, $gymCurrency) }}.
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
                            <button type="submit" class="btn btn-primary">Replace Card</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif
