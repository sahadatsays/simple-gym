@extends('layouts.admin', ['heading' => __('dashboard.heading')])

@section('title', __('dashboard.heading'))

@section('content')
    <x-ui.page-header
        :title="__('dashboard.title')"
        :subtitle="__('common.misc.performance_overview', ['range' => $stats['range_label']])"
    />

    <x-dashboard.quick-actions />

    <x-dashboard.date-range-filter :filters="$filters" />

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.new_registrations')"
                :value="$stats['new_registrations']"
                icon="users"
                variant="primary"
            >
                <x-slot:footer>{{ __('dashboard.stats.new_registrations_footer') }}</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.active_members')"
                :value="$stats['active_members']"
                icon="user-check"
                variant="success"
            >
                <x-slot:footer>{{ __('dashboard.stats.active_members_footer') }}</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.expired_in_period')"
                :value="$stats['expired_members']"
                icon="user-x"
                variant="danger"
            >
                <x-slot:footer>{{ __('dashboard.stats.expired_in_period_footer') }}</x-slot:footer>
            </x-dashboard.stat-card>
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.period_revenue')"
                :value="App\Support\MoneyFormatter::format($stats['period_revenue'], $stats['currency'])"
                icon="wallet"
                variant="info"
                formatted
            />
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.product_sales')"
                :value="App\Support\MoneyFormatter::format($stats['product_sales'], $stats['currency'])"
                icon="shopping"
                variant="warning"
                formatted
            />
        </div>

        <div class="col-6 col-xl-4 col-xxl-2">
            <x-dashboard.stat-card
                :title="__('dashboard.stats.low_stock_items')"
                :value="$stats['low_stock_products']"
                icon="alert"
                variant="dark"
            >
                <x-slot:footer>{{ __('dashboard.stats.low_stock_items_footer') }}</x-slot:footer>
            </x-dashboard.stat-card>
        </div>
    </div>

    @if ($assetInvestmentStats !== null)
        <h2 class="h5 fw-bold mb-3">{{ __('dashboard.sections.assets_investments') }}</h2>

        <div class="row g-3 g-xl-4 mb-4">
            @can('viewAny', App\Models\Investment::class)
                <div class="col-6 col-xl-3">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.total_owner_investment')"
                        :value="App\Support\MoneyFormatter::format($assetInvestmentStats['total_owner_investment'], $stats['currency'])"
                        icon="wallet"
                        variant="primary"
                        formatted
                    >
                        <x-slot:footer>{{ __('dashboard.stats.in_selected_period') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>
            @endcan

            @can('viewAny', App\Models\Asset::class)
                <div class="col-6 col-xl-3">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.total_asset_purchase_value')"
                        :value="App\Support\MoneyFormatter::format($assetInvestmentStats['total_asset_purchase_value'], $stats['currency'])"
                        icon="shopping"
                        variant="info"
                        formatted
                    >
                        <x-slot:footer>{{ __('dashboard.stats.in_selected_period') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>

                <div class="col-6 col-xl-3">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.current_asset_value')"
                        :value="App\Support\MoneyFormatter::format($assetInvestmentStats['current_asset_value'], $stats['currency'])"
                        icon="chart"
                        variant="success"
                        formatted
                    >
                        <x-slot:footer>{{ __('dashboard.stats.active_assets_today') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>
            @endcan

            @can('viewAny', App\Models\AssetMaintenance::class)
                <div class="col-6 col-xl-3">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.total_maintenance_cost')"
                        :value="App\Support\MoneyFormatter::format($assetInvestmentStats['total_maintenance_cost'], $stats['currency'])"
                        icon="wallet"
                        variant="warning"
                        formatted
                    >
                        <x-slot:footer>{{ __('dashboard.stats.in_selected_period') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>
            @endcan
        </div>

        <div class="row g-3 g-xl-4 mb-4">
            @can('viewAny', App\Models\Asset::class)
                <div class="col-6 col-xl-4">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.active_assets')"
                        :value="$assetInvestmentStats['active_assets']"
                        icon="user-check"
                        variant="success"
                    >
                        <x-slot:footer>{{ __('dashboard.stats.currently_active') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>

                <div class="col-6 col-xl-4">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.assets_under_maintenance')"
                        :value="$assetInvestmentStats['assets_under_maintenance']"
                        icon="alert"
                        variant="warning"
                    >
                        <x-slot:footer>{{ __('dashboard.stats.current_status') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>

                <div class="col-6 col-xl-4">
                    <x-dashboard.stat-card
                        :title="__('dashboard.stats.assets_requiring_maintenance')"
                        :value="$assetInvestmentStats['assets_requiring_maintenance']"
                        icon="alert"
                        variant="danger"
                    >
                        <x-slot:footer>{{ __('dashboard.stats.due_or_overdue') }}</x-slot:footer>
                    </x-dashboard.stat-card>
                </div>
            @endcan
        </div>
    @endif

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-lg-7">
            <x-dashboard.widget :title="__('dashboard.charts.revenue_trend')" :subtitle="$stats['range_label']">
                <x-dashboard.chart
                    id="revenueTrendChart"
                    type="line"
                    :labels="$revenueSeries['labels']"
                    :values="$revenueSeries['values']"
                    :label="__('dashboard.charts.revenue_label')"
                    color="#2563eb"
                    :currency="$stats['currency']"
                />
            </x-dashboard.widget>
        </div>

        <div class="col-lg-5">
            <x-dashboard.widget :title="__('dashboard.charts.registration_trend')" :subtitle="$stats['range_label']">
                <x-dashboard.chart
                    id="registrationTrendChart"
                    type="bar"
                    :labels="$registrationSeries['labels']"
                    :values="$registrationSeries['values']"
                    :label="__('dashboard.charts.registrations_label')"
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

        <div class="col-xl-6">
            <x-dashboard.low-stock-products :products="$lowStockProducts" :currency="$stats['currency']" />
        </div>

        @can('viewAny', App\Models\Payment::class)
            <div class="col-xl-6">
                <x-dashboard.upcoming-due-orders :orders="$upcomingDueOrders" :currency="$stats['currency']" />
            </div>
        @endcan

        @can('viewAny', App\Models\Investment::class)
            <div class="col-xl-6">
                <x-dashboard.recent-investments :investments="$recentInvestments" :currency="$stats['currency']" />
            </div>
        @endcan

        @can('viewAny', App\Models\Asset::class)
            <div class="col-xl-6">
                <x-dashboard.recent-asset-purchases :assets="$recentAssetPurchases" :currency="$stats['currency']" />
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
