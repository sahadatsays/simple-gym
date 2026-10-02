@props([
    'borrowings',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_borrowings')" :subtitle="__('dashboard.widgets.recent_borrowings_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.widgets.borrowing_number') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('dashboard.widgets.lender') }}</th>
                    <th class="text-end">{{ __('common.table.amount') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('common.table.date') }}</th>
                    <th class="d-none d-lg-table-cell">{{ __('common.table.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($borrowings as $borrowing)
                    <tr>
                        <td>
                            <a href="{{ route('admin.borrowings.show', $borrowing) }}" class="fw-semibold text-decoration-none">
                                {{ $borrowing->borrowing_no }}
                            </a>
                            <div class="small text-muted d-sm-none">{{ $borrowing->lender_name }}</div>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $borrowing->lender_name }}</td>
                        <td class="fw-semibold text-end text-nowrap">
                            {{ App\Support\MoneyFormatter::format($borrowing->amount, $currency) }}
                        </td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $borrowing->borrowing_date->format('M j, Y') }}
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span class="sg-status-badge {{ $borrowing->status->badgeClass() }}">
                                {{ $borrowing->status->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_borrowings_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($borrowings->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.borrowings.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_borrowings') }}</a>
        </div>
    @endif
</x-dashboard.widget>
