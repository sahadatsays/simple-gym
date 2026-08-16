<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $type->label() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 16px; }
        .summary { margin-bottom: 16px; }
        .summary span { display: inline-block; margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f4f6; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $type->label() }}</h1>
    <div class="meta">
        {{ config('app.name') }} ·
        {{ $filters['from_date'] ?? '—' }} to {{ $filters['to_date'] ?? '—' }} ·
        Generated {{ $generatedAt->format('M j, Y g:i A') }}
    </div>

    <div class="summary">
        @foreach ($payload['summary'] as $key => $value)
            <span><strong>{{ str($key)->headline() }}:</strong> {{ $value }}</span>
        @endforeach
    </div>

    @if (! empty($payload['category_summary'] ?? null))
        <div class="summary">
            <strong>Category-wise Expense Total</strong>
        </div>
        <table style="margin-bottom: 16px;">
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
                        <td class="text-end">{{ $row['total_amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($payload['columns'] as $column)
                    <th @class(['text-end' => ($column['align'] ?? '') === 'end'])>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($payload['columns'] as $column)
                        <td @class(['text-end' => ($column['align'] ?? '') === 'end'])>
                            {{ $row[$column['key']] ?? '—' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($payload['columns']) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
