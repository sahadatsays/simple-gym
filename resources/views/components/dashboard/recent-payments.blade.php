@props([
    'payments',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_payments')" :subtitle="__('dashboard.widgets.recent_payments_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('common.table.receipt') }}</th>
                    <th>{{ __('common.table.member') }}</th>
                    <th class="text-end">{{ __('common.table.amount') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('common.table.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>
                            <a href="{{ route('admin.payments.show', $payment) }}" class="fw-semibold text-decoration-none">
                                {{ $payment->receipt_number }}
                            </a>
                            <div class="small text-muted">{{ __('enums.payment_type.'.$payment->type->value) }}</div>
                        </td>
                        <td>{{ $payment->member?->name ?? __('common.customer.walk_in') }}</td>
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
                            {{ __('dashboard.widgets.no_payments_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_payments') }}</a>
        </div>
    @endif
</x-dashboard.widget>
