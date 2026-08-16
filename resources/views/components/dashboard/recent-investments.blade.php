@props([
    'investments',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_investments')" :subtitle="__('dashboard.widgets.recent_investments_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('dashboard.widgets.investment_number') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('common.table.category') }}</th>
                    <th class="text-end">{{ __('common.table.amount') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('common.table.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($investments as $investment)
                    <tr>
                        <td>
                            <a href="{{ route('admin.investments.show', $investment) }}" class="fw-semibold text-decoration-none">
                                {{ $investment->investment_number }}
                            </a>
                            <div class="small text-muted d-sm-none">{{ $investment->category?->name ?? '—' }}</div>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $investment->category?->name ?? '—' }}</td>
                        <td class="fw-semibold text-end text-nowrap">
                            {{ App\Support\MoneyFormatter::format($investment->amount, $currency) }}
                        </td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $investment->invested_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_investments_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($investments->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.investments.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_investments') }}</a>
        </div>
    @endif
</x-dashboard.widget>
