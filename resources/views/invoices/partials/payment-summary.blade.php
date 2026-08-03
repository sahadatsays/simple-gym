<div class="invoice-summary-grid">
    <div class="invoice-totals">
        <div class="invoice-total-row">
            <span>Subtotal</span>
            <span>{{ App\Support\MoneyFormatter::format($summary['subtotal'], $currency) }}</span>
        </div>
        @if ($summary['discount'] > 0)
            <div class="invoice-total-row">
                <span>Discount</span>
                <span>-{{ App\Support\MoneyFormatter::format($summary['discount'], $currency) }}</span>
            </div>
        @endif
        <div class="invoice-total-row invoice-total-row-strong">
            <span>Invoice Total</span>
            <span>{{ App\Support\MoneyFormatter::format($summary['total'], $currency) }}</span>
        </div>
        <div class="invoice-total-row">
            <span>Amount Paid</span>
            <span>{{ App\Support\MoneyFormatter::format($summary['amount_paid'], $currency) }}</span>
        </div>
        <div class="invoice-total-row invoice-total-row-balance">
            <span>Outstanding Balance</span>
            <span>{{ App\Support\MoneyFormatter::format($summary['outstanding_balance'], $currency) }}</span>
        </div>
    </div>

    @if (! empty($payment_summary))
        <div class="invoice-payment-summary">
            <h2 class="invoice-section-title">Payment Summary</h2>
            <table class="invoice-table invoice-table-compact">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payment_summary as $entry)
                        <tr>
                            <td>{{ $entry['receipt_number'] }}</td>
                            <td>{{ $entry['paid_at'] }}</td>
                            <td>{{ $entry['method'] }}</td>
                            <td class="text-end">{{ App\Support\MoneyFormatter::format($entry['amount'], $currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
