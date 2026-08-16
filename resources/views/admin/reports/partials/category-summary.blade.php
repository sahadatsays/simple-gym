@props([
    'categorySummary',
])

@if ($categorySummary->isNotEmpty())
    <x-dashboard.widget title="Category-wise Expense Total" subtitle="Totals for the selected filters">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sg-dashboard-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-end">Expenses</th>
                        <th class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorySummary as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['category'] }}</td>
                            <td class="text-end text-muted">{{ $row['expense_count'] }}</td>
                            <td class="text-end fw-semibold text-nowrap">
                                {{ App\Support\MoneyFormatter::format($row['total_amount'], $gymCurrency) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-dashboard.widget>
@endif
