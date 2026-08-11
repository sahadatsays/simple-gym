@extends('layouts.admin', ['heading' => 'RFID Cards'])

@section('title', 'RFID Cards')

@section('content')
    <x-ui.page-header title="RFID Cards" subtitle="Register, assign, replace, disable, and enable member access cards">
        <x-slot:actions>
            @can('create', App\Models\RfidCard::class)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerCardModal">
                    Register Card
                </button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.rfid-cards.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Card number or member..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\RfidCardStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.rfid-cards.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th>Card Number</th>
                            <th class="d-none d-md-table-cell">Assigned Member</th>
                            <th class="d-none d-lg-table-cell">Assignment Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cards as $card)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $card->card_number }}</div>
                                    @if ($card->member)
                                        <div class="small text-muted d-md-none">
                                            {{ $card->member->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if ($card->member)
                                        <div>{{ $card->member->name }}</div>
                                        <div class="small text-muted">{{ $card->member->member_code }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell text-muted">
                                    {{ $card->assigned_at?->format('M j, Y g:i A') ?? '—' }}
                                </td>
                                <td>
                                    <span class="sg-status-badge {{ $card->status->badgeClass() }}">
                                        {{ $card->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.rfid-card-actions :card="$card" :members="$members" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No RFID cards found</h3>
                                        <p class="text-muted small mb-0">Register a card or adjust your search.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($cards->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $cards->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @can('create', App\Models\RfidCard::class)
        <div class="modal fade" id="registerCardModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.store') }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Register RFID Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <x-forms.input
                                label="Card number"
                                name="card_number"
                                placeholder="Scan or enter RFID"
                                required
                            />
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Register Card</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('replace', App\Models\RfidCard::class)
        <div class="modal fade" id="globalReplaceCardModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.rfid-cards.replace') }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Replace Member Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <x-forms.searchable-select
                                label="Member"
                                name="member_id"
                                id="replace-member-global"
                                :options="$members->mapWithKeys(fn ($member) => [$member->id => $member->name.' ('.$member->member_code.')'])->all()"
                                placeholder="Search member..."
                                required
                            />
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
@endsection
