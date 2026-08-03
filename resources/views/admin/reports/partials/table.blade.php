@props(['columns', 'rows'])

@php
    $moneyKeys = [
        'admission_fee', 'membership_fee', 'pos_sale', 'total', 'discount', 'amount',
        'unit_price', 'line_total', 'profit', 'purchase_value', 'retail_value',
    ];

    $items = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $rows->items()
        : $rows;
@endphp

<div class="card border-0 shadow-sm sg-data-table-card">
    <div class="card-body p-0">
        <div class="table-responsive sg-data-table-wrapper">
            <table class="table table-hover align-middle mb-0 sg-data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th @class(['text-end' => ($column['align'] ?? '') === 'end', 'ps-4' => $loop->first])>
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $row)
                        <tr>
                            @foreach ($columns as $column)
                                @php
                                    $value = $row[$column['key']] ?? '—';
                                    $isMoney = in_array($column['key'], $moneyKeys, true) && is_numeric($value);
                                @endphp
                                <td @class(['text-end' => ($column['align'] ?? '') === 'end', 'ps-4' => $loop->parent->first])>
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
                            <td colspan="{{ count($columns) }}" class="text-center text-muted py-5">
                                No records found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $rows->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $rows->links() }}
        </div>
    @endif
</div>
