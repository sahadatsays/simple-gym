@props(['type', 'summary'])

@php
    $moneyKeys = [
        'total', 'admission_fee', 'membership_fee', 'pos_sale', 'total_sales', 'total_discount',
        'total_revenue', 'gross_profit', 'total_retail_value', 'total_investment',
        'total_purchase_value', 'total_current_value', 'total_maintenance_cost', 'current_asset_value',
    ];

    $labels = [
        'total' => 'Total Collection',
        'transaction_count' => 'Transactions',
        'admission_fee' => 'Admission Fees',
        'membership_fee' => 'Membership Fees',
        'pos_sale' => 'POS Sales',
        'total_members' => 'Total Members',
        'active_members' => 'Active Members',
        'expired_members' => 'Expired Members',
        'pending_members' => 'Pending Members',
        'expired_count' => 'Expired Members',
        'expiring_count' => 'Expiring Soon',
        'window_start' => 'Window Start',
        'window_end' => 'Window End',
        'total_sales' => 'Total Sales',
        'total_discount' => 'Total Discount',
        'units_sold' => 'Units Sold',
        'sales_count' => 'Sales Count',
        'total_revenue' => 'Total Revenue',
        'gross_profit' => 'Gross Profit',
        'total_products' => 'Total Products',
        'active_products' => 'Active Products',
        'low_stock_products' => 'Low Stock',
        'out_of_stock_products' => 'Out of Stock',
        'total_retail_value' => 'Retail Value',
        'total_investment' => 'Total Investment',
        'investment_count' => 'Investments',
        'asset_count' => 'Assets',
        'total_purchase_value' => 'Total Purchase Value',
        'total_current_value' => 'Current Asset Value',
        'maintenance_count' => 'Maintenance Records',
        'total_maintenance_cost' => 'Total Maintenance Cost',
        'current_asset_value' => 'Current Asset Value',
    ];
@endphp

<div class="row g-3 mb-4">
    @foreach ($summary as $key => $value)
        <div class="col-md-6 col-xl-3">
            <x-dashboard.stat-card
                :title="$labels[$key] ?? str($key)->headline()"
                :value="in_array($key, $moneyKeys, true)
                    ? App\Support\MoneyFormatter::format($value, $gymCurrency)
                    : $value"
                :formatted="in_array($key, $moneyKeys, true)"
                variant="{{ in_array($key, ['expired_members', 'expired_count', 'out_of_stock_products', 'low_stock_products'], true) ? 'danger' : 'primary' }}"
            />
        </div>
    @endforeach
</div>
