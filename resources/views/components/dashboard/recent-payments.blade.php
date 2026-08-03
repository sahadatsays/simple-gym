@props([
    'payments',
    'currency',
])

<x-dashboard.widget title="Recent Payments" subtitle="Transactions in the selected period">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>Receipt</th>
                    <th>Member</th>
                    <th class="text-end">Amount</th>
                    <th class="d-none d-md-table-cell">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>
                            <a href="{{ route('admin.payments.show', $payment) }}" class="fw-semibold text-decoration-none">
                                {{ $payment->receipt_number }}
                            </a>
                            <div class="small text-muted">{{ $payment->type->label() }}</div>
                        </td>
                        <td>{{ $payment->member?->name ?? 'Walk-in' }}</td>
                        <td class="fw-semibold text-end text-nowrap">
                            {{ App\Support\MoneyFormatter::format($payment->amount, $currency) }}
                        </td>
                        <td class="text-nowrap d-none d-md-table-cell text-muted">
                            {{ $payment->paid_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No payments recorded for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-light">View all payments</a>
        </div>
    @endif
</x-dashboard.widget>
