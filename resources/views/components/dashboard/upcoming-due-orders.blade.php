@props([
    'orders',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.upcoming_due_orders')" :subtitle="__('dashboard.widgets.upcoming_due_orders_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('common.table.order') }}</th>
                    <th>{{ __('common.table.customer') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('common.table.due') }}</th>
                    <th class="text-end">{{ __('common.table.balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php($isOverdue = $order->due_at?->lt(now()->startOfDay()))
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-decoration-none">
                                {{ $order->invoice_number }}
                            </a>
                            <div class="small text-muted d-sm-none">
                                {{ $order->due_at?->format('M j, Y') ?? '—' }}
                            </div>
                        </td>
                        <td>
                            @if ($order->member)
                                <div>{{ $order->member->name }}</div>
                                <div class="small text-muted">{{ $order->member->member_code }}</div>
                            @else
                                <span class="text-muted">{{ __('common.customer.walk_in') }}</span>
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell">
                            <span class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $order->due_at?->format('M j, Y') ?? '—' }}
                            </span>
                            @if ($isOverdue)
                                <div class="small text-danger">{{ __('common.status.overdue') }}</div>
                            @elseif ($order->due_at?->isToday())
                                <div class="small text-warning-emphasis">{{ __('common.status.due_today') }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold text-danger text-nowrap">
                                {{ App\Support\MoneyFormatter::format($order->outstandingBalance(), $currency) }}
                            </span>
                            <div class="small text-muted d-none d-md-block">{{ __('enums.invoice_status.'.$order->status->value) }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_upcoming_due', ['days' => config('gym.dashboard.due_orders_lookahead_days', 30)]) }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.orders.index', ['status' => 'unpaid']) }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_orders') }}</a>
        </div>
    @endif
</x-dashboard.widget>
