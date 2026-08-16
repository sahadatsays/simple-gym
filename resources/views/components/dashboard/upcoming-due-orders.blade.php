@props([
    'orders',
    'currency',
])

<x-dashboard.widget title="Upcoming Due Orders" subtitle="Open POS balances due soon or overdue">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th class="d-none d-sm-table-cell">Due</th>
                    <th class="text-end">Balance</th>
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
                                <span class="text-muted">Walk-in</span>
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell">
                            <span class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $order->due_at?->format('M j, Y') ?? '—' }}
                            </span>
                            @if ($isOverdue)
                                <div class="small text-danger">Overdue</div>
                            @elseif ($order->due_at?->isToday())
                                <div class="small text-warning-emphasis">Due today</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold text-danger text-nowrap">
                                {{ App\Support\MoneyFormatter::format($order->outstandingBalance(), $currency) }}
                            </span>
                            <div class="small text-muted d-none d-md-block">{{ $order->status->label() }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No upcoming due orders in the next {{ config('gym.dashboard.due_orders_lookahead_days', 30) }} days.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.orders.index', ['status' => 'unpaid']) }}" class="btn btn-sm btn-light">View all orders</a>
        </div>
    @endif
</x-dashboard.widget>
