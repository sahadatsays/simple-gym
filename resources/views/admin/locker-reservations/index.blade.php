@extends('layouts.admin', ['heading' => 'Locker Reservations'])

@section('title', 'Locker Reservations')

@section('content')
    <x-ui.page-header title="Locker Reservations" subtitle="Monthly locker reservations, invoices, and history">
        <x-slot:actions>
            <a href="{{ route('admin.locker-reservations.renewals') }}" class="btn btn-light">
                Renewals
            </a>
            @can('create', App\Models\LockerReservation::class)
                <a href="{{ route('admin.locker-reservations.create') }}" class="btn btn-primary">
                    Reserve Locker
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.locker-reservations.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Locker, location, or member..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\LockerReservationStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.locker-reservations.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Locker</th>
                            <th>Member</th>
                            <th class="d-none d-md-table-cell">Start</th>
                            <th class="d-none d-lg-table-cell">End</th>
                            <th>Monthly Fee</th>
                            <th>Status</th>
                            <th class="d-none d-xl-table-cell">Invoice</th>
                            <th class="d-none d-xl-table-cell">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.locker-reservations.show', $reservation) }}" class="fw-semibold text-decoration-none">
                                        {{ $reservation->locker?->locker_number ?? '—' }}
                                    </a>
                                    <div class="small text-muted d-md-none">
                                        {{ $reservation->start_date->format('M j, Y') }} – {{ $reservation->end_date->format('M j, Y') }}
                                    </div>
                                </td>
                                <td>{{ $reservation->member?->name ?? '—' }}</td>
                                <td class="d-none d-md-table-cell">{{ $reservation->start_date->format('M j, Y') }}</td>
                                <td class="d-none d-lg-table-cell">{{ $reservation->end_date->format('M j, Y') }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($reservation->monthly_fee, $gymCurrency) }}</td>
                                <td>
                                    <span class="sg-status-badge {{ $reservation->status->badgeClass() }}">{{ $reservation->status->label() }}</span>
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    @if ($reservation->invoice)
                                        <a href="{{ route('admin.invoices.show', $reservation->invoice) }}">{{ $reservation->invoice->invoice_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">{{ $reservation->creator?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No locker reservations found</h3>
                                        <p class="text-muted small mb-0">Reserve a locker or adjust your search.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($reservations->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $reservations->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
