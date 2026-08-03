<table class="invoice-table">
    <thead>
        <tr>
            <th>Description</th>
            @if (collect($invoice->line_items)->contains(fn ($item) => isset($item['quantity'])))
                <th class="text-end">Qty</th>
                <th class="text-end">Unit</th>
            @endif
            <th class="text-end">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->line_items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                @if (collect($invoice->line_items)->contains(fn ($line) => isset($line['quantity'])))
                    <td class="text-end">{{ $item['quantity'] ?? '—' }}</td>
                    <td class="text-end">
                        @if (isset($item['unit_price']))
                            {{ App\Support\MoneyFormatter::format($item['unit_price'], $currency) }}
                        @else
                            —
                        @endif
                    </td>
                @endif
                <td class="text-end">{{ App\Support\MoneyFormatter::format($item['amount'], $currency) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
