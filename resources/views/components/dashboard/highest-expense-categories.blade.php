@props([
    'categories',
    'currency',
])

<x-dashboard.widget :title="__('dashboard.widgets.highest_expense_categories')" :subtitle="__('dashboard.widgets.highest_expense_categories_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('common.table.category') }}</th>
                    <th class="text-end d-none d-sm-table-cell">{{ __('dashboard.widgets.expense_count') }}</th>
                    <th class="text-end">{{ __('common.table.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="fw-semibold">{{ $category->category_name }}</td>
                        <td class="text-end text-muted d-none d-sm-table-cell">{{ $category->expense_count }}</td>
                        <td class="fw-semibold text-end text-nowrap">
                            {{ App\Support\MoneyFormatter::format($category->total_amount, $currency) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_expense_categories_recorded') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-dashboard.widget>
