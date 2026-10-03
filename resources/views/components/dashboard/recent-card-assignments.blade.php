@props([
    'assignments',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_card_assignments')" :subtitle="__('dashboard.widgets.recent_card_assignments_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.widgets.card') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('dashboard.widgets.member') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('dashboard.widgets.issue_date') }}</th>
                    <th class="d-none d-lg-table-cell">{{ __('common.table.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr>
                        <td>
                            @if ($assignment->rfidCard)
                                <a href="{{ route('admin.rfid-cards.show', $assignment->rfidCard) }}" class="fw-semibold text-decoration-none">
                                    {{ $assignment->rfidCard->card_number }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            <div class="small text-muted d-sm-none">{{ $assignment->member?->name ?? '—' }}</div>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $assignment->member?->name ?? '—' }}</td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $assignment->issue_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span class="sg-status-badge {{ $assignment->status->badgeClass() }}">
                                {{ $assignment->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_card_assignments') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assignments->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.rfid-cards.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_rfid_cards') }}</a>
        </div>
    @endif
</x-dashboard.widget>
