@props(['card', 'members'])

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

            @can('disable', $card)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form
                        action="{{ route('admin.rfid-cards.disable', $card) }}"
                        method="POST"
                        onsubmit="return confirm('Disable this RFID card?');"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item text-danger">Disable Card</button>
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
                                Assign <strong>{{ $card->card_number }}</strong> to a member. Any existing active card for that member will be disabled.
                            </p>
                            <x-forms.searchable-select
                                label="Member"
                                name="member_id"
                                id="assign-member-{{ $card->id }}"
                                :options="$members->mapWithKeys(fn ($member) => [$member->id => $member->name.' ('.$member->member_code.')'])->all()"
                                placeholder="Search member..."
                                required
                            />
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
                                Replace the active card for <strong>{{ $card->member->name }}</strong>. The current card will be disabled automatically.
                            </p>
                            <x-forms.input
                                label="New card number"
                                name="card_number"
                                placeholder="Scan or enter new RFID"
                                required
                            />
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
