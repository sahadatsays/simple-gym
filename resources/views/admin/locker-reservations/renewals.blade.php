@extends('layouts.admin', ['heading' => 'Renew Lockers'])

@section('title', 'Renew Lockers')

@section('content')
    <x-ui.page-header
        title="Locker Renewal"
        subtitle="Expired reservations and reservations ending within {{ $reminderDays }} days"
    >
        <x-slot:actions>
            <a href="{{ route('admin.locker-reservations.index') }}" class="btn btn-light">Reservations</a>
            @can('create', App\Models\LockerReservation::class)
                <a href="{{ route('admin.locker-reservations.create') }}" class="btn btn-primary">Reserve Locker</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-forms.error-summary />

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.locker-reservations.renewals') }}" method="GET" class="sg-filter-grid">
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

            <x-admin.filter-field label="Sort by end date" for="direction">
                <select name="direction" id="direction" class="form-select">
                    <option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>Soonest first</option>
                    <option value="desc" @selected(($filters['direction'] ?? 'asc') === 'desc')>Latest first</option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.locker-reservations.renewals') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <p class="text-muted small mb-3">
        {{ $reservations->total() }} {{ $reservations->total() === 1 ? 'reservation' : 'reservations' }} can be renewed
    </p>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Locker</th>
                            <th>Member</th>
                            <th class="d-none d-md-table-cell">End</th>
                            <th class="d-none d-lg-table-cell">Status</th>
                            <th class="d-none d-xl-table-cell">Monthly Fee</th>
                            <th class="text-end pe-4">Renew</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            @php
                                $daysUntilExpiry = $reservation->daysUntilExpiry();
                                $monthlyFee = (float) ($reservation->locker->monthly_fee ?? 0);
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.locker-reservations.show', $reservation) }}" class="fw-semibold text-decoration-none">
                                        {{ $reservation->locker?->locker_number ?? '—' }}
                                    </a>
                                    <div class="small text-muted d-md-none">
                                        Ends {{ $reservation->end_date->format('M j, Y') }}
                                    </div>
                                </td>
                                <td>{{ $reservation->member?->name ?? '—' }}</td>
                                <td class="d-none d-md-table-cell">{{ $reservation->end_date->format('M j, Y') }}</td>
                                <td class="d-none d-lg-table-cell">
                                    @if ($daysUntilExpiry < 0)
                                        <span class="badge text-bg-danger">Expired {{ abs($daysUntilExpiry) }} {{ abs($daysUntilExpiry) === 1 ? 'day' : 'days' }} ago</span>
                                    @elseif ($daysUntilExpiry === 0)
                                        <span class="badge text-bg-warning">Ends today</span>
                                    @else
                                        <span class="badge text-bg-warning">{{ $daysUntilExpiry }} {{ $daysUntilExpiry === 1 ? 'day' : 'days' }} left</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">{{ App\Support\MoneyFormatter::format($monthlyFee, $gymCurrency) }}</td>
                                <td class="text-end pe-4">
                                    @if ($reservation->locker?->canBeReserved())
                                        @can('renew', $reservation)
                                            <form action="{{ route('admin.locker-reservations.renew', $reservation) }}" method="POST" class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                                                @csrf
                                                @if ($monthlyFee > 0)
                                                    <select name="payment_method" class="form-select form-select-sm" style="max-width: 9rem;" required>
                                                        @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                                                            <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                                <button type="submit" class="btn btn-sm btn-primary">Renew</button>
                                            </form>
                                        @endcan
                                    @else
                                        <span class="text-muted small">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No lockers need renewal</h3>
                                        <p class="text-muted small mb-0">Expired and soon-ending reservations appear here.</p>
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
                {{ $reservations->links() }}
            </div>
        @endif
    </div>
@endsection
