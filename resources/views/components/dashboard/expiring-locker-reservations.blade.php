@props([
    'reservations',
])

<x-dashboard.widget :title="__('dashboard.widgets.expiring_locker_reservations')" :subtitle="__('dashboard.widgets.expiring_locker_reservations_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.widgets.locker') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('dashboard.widgets.member') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('dashboard.widgets.start_date') }}</th>
                    <th class="d-none d-lg-table-cell">{{ __('dashboard.widgets.end_date') }}</th>
                    <th class="d-none d-xl-table-cell">{{ __('common.table.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $reservation)
                    <tr>
                        <td>
                            @if ($reservation->locker)
                                <a href="{{ route('admin.lockers.show', $reservation->locker) }}" class="fw-semibold text-decoration-none">
                                    {{ $reservation->locker->locker_number }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            <div class="small text-muted d-sm-none">{{ $reservation->member?->name ?? '—' }}</div>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $reservation->member?->name ?? '—' }}</td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $reservation->start_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="text-nowrap d-none d-lg-table-cell text-muted">
                            {{ $reservation->end_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="d-none d-xl-table-cell">
                            <span class="sg-status-badge {{ $reservation->status->badgeClass() }}">
                                {{ $reservation->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_expiring_lockers') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reservations->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.locker-reservations.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_locker_reservations') }}</a>
        </div>
    @endif
</x-dashboard.widget>
