@extends('layouts.admin', ['heading' => 'Dashboard'])

@section('title', 'Dashboard')

@section('content')
    <x-ui.page-header
        title="Business Dashboard"
        :subtitle="'Performance overview · '.$stats['range_label']"
    />

    <x-dashboard.date-range-filter :filters="$filters" />

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="New Registrations"
                :value="$stats['new_registrations']"
                icon="users"
                variant="primary"
            >
                <x-slot:footer>In selected period</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="Active Members"
                :value="$stats['active_members']"
                icon="user-check"
                variant="success"
            >
                <x-slot:footer>Currently active</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="Expired in Period"
                :value="$stats['expired_members']"
                icon="user-x"
                variant="danger"
            >
                <x-slot:footer>Memberships expired</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="Period Revenue"
                :value="App\Support\MoneyFormatter::format($stats['period_revenue'], $stats['currency'])"
                icon="wallet"
                variant="info"
                formatted
            />
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="Product Sales"
                :value="App\Support\MoneyFormatter::format($stats['product_sales'], $stats['currency'])"
                icon="shopping"
                variant="warning"
                formatted
            />
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                title="Low Stock Items"
                :value="$stats['low_stock_products']"
                icon="alert"
                variant="dark"
            >
                <x-slot:footer>Needs restocking</x-slot:footer>
            </x-dashboard.stat-card>
        </div>
    </div>

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-lg-7">
            <x-dashboard.widget title="Revenue Trend" :subtitle="$stats['range_label']">
                <x-dashboard.chart
                    id="revenueTrendChart"
                    type="line"
                    :labels="$revenueSeries['labels']"
                    :values="$revenueSeries['values']"
                    label="Revenue"
                    color="#2563eb"
                    :currency="$stats['currency']"
                />
            </x-dashboard.widget>
        </div>

        <div class="col-lg-5">
            <x-dashboard.widget title="Registration Trend" :subtitle="$stats['range_label']">
                <x-dashboard.chart
                    id="registrationTrendChart"
                    type="bar"
                    :labels="$registrationSeries['labels']"
                    :values="$registrationSeries['values']"
                    label="Registrations"
                    color="#16a34a"
                />
            </x-dashboard.widget>
        </div>
    </div>

    <div class="row g-3 g-xl-4">
        <div class="col-xl-6">
            <x-dashboard.recent-payments :payments="$recentPayments" :currency="$stats['currency']" />
        </div>

        <div class="col-xl-6">
            <x-dashboard.recent-registrations :members="$recentRegistrations" />
        </div>

        <div class="col-12">
            <x-dashboard.low-stock-products :products="$lowStockProducts" :currency="$stats['currency']" />
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
