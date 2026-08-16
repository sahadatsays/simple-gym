<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $type->label() }} | {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 2rem;
            font-size: 12px;
        }

        h1 {
            font-size: 1.5rem;
            margin: 0 0 0.25rem;
        }

        .meta {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .summary-card strong {
            display: block;
            font-size: 1rem;
            margin-top: 0.25rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.625rem;
            text-align: left;
        }

        th {
            background: #f8fafc;
        }

        .text-end {
            text-align: right;
        }

        @media print {
            body {
                margin: 0.5rem;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    @php
        $moneyKeys = [
            'admission_fee', 'membership_fee', 'pos_sale', 'total', 'discount', 'amount',
            'unit_price', 'line_total', 'profit', 'purchase_value', 'retail_value',
            'purchase_price', 'current_value', 'cost',
        ];

        $items = $payload['rows'] instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
            ? $payload['rows']->items()
            : $payload['rows'];
    @endphp

    <div class="no-print" style="margin-bottom: 1rem;">
        <button type="button" onclick="window.print()">Print Report</button>
    </div>

    <h1>{{ $type->label() }}</h1>
    <div class="meta">
        {{ $gymName ?? config('app.name') }}
        @if (filled($filters['from_date'] ?? null) || filled($filters['to_date'] ?? null))
            · {{ $filters['from_date'] ?? '—' }} to {{ $filters['to_date'] ?? '—' }}
        @endif
        · Generated {{ now()->format('M j, Y g:i A') }}
    </div>

    <div class="summary">
        @foreach ($payload['summary'] as $key => $value)
            <div class="summary-card">
                <span>{{ str($key)->headline() }}</span>
                <strong>
                    @if (in_array($key, ['total', 'admission_fee', 'membership_fee', 'pos_sale', 'total_sales', 'total_discount', 'total_revenue', 'gross_profit', 'total_retail_value', 'total_investment', 'total_purchase_value', 'total_current_value', 'total_maintenance_cost', 'current_asset_value', 'total_expense', 'revenue', 'expenses', 'net_operating_result', 'owner_investment'], true) && is_numeric($value))
                        {{ App\Support\MoneyFormatter::format($value, $gymCurrency) }}
                    @else
                        {{ $value }}
                    @endif
                </strong>
            </div>
        @endforeach
    </div>

    @if (! empty($payload['category_summary'] ?? null))
        <h2 style="font-size: 1rem; margin: 0 0 0.75rem;">Category-wise Expense Total</h2>
        <table style="margin-bottom: 1.5rem;">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-end">Expenses</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['category_summary'] as $row)
                    <tr>
                        <td>{{ $row['category'] }}</td>
                        <td class="text-end">{{ $row['expense_count'] }}</td>
                        <td class="text-end">{{ App\Support\MoneyFormatter::format($row['total_amount'], $gymCurrency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($payload['columns']) > 0)
        <table>
            <thead>
                <tr>
                    @foreach ($payload['columns'] as $column)
                        <th @class(['text-end' => ($column['align'] ?? '') === 'end'])>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $row)
                    <tr>
                        @foreach ($payload['columns'] as $column)
                            @php
                                $value = $row[$column['key']] ?? '—';
                                $isMoney = in_array($column['key'], $moneyKeys, true) && is_numeric($value);
                            @endphp
                            <td @class(['text-end' => ($column['align'] ?? '') === 'end'])>
                                @if ($isMoney)
                                    {{ App\Support\MoneyFormatter::format($value, $gymCurrency) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($payload['columns']) }}">No records found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
