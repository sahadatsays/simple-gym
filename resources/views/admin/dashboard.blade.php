@extends('layouts.admin', ['heading' => 'Dashboard'])

@section('title', 'Dashboard')

@section('content')
    <x-ui.page-header
        title="Dashboard"
        subtitle="Real-time overview of members, revenue, and inventory"
    />

    <x-dashboard.alerts :alerts="$alerts" />

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Total Members"
                :value="$stats['total_members']"
                icon="users"
                variant="primary"
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Active Members"
                :value="$stats['active_members']"
                icon="user-check"
                variant="success"
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Expired Members"
                :value="$stats['expired_members']"
                icon="user-x"
                variant="danger"
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Today's Collection"
                :value="App\Support\MoneyFormatter::format($stats['todays_collection'], $stats['currency'])"
                icon="wallet"
                variant="info"
                formatted
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Monthly Collection"
                :value="App\Support\MoneyFormatter::format($stats['monthly_collection'], $stats['currency'])"
                icon="chart"
                variant="purple"
                formatted
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Product Sales"
                :value="App\Support\MoneyFormatter::format($stats['product_sales'], $stats['currency'])"
                icon="shopping"
                variant="warning"
                formatted
            />
        </div>

        <div class="col-6 col-xl-3">
            <x-dashboard.stat-card
                title="Low Stock Products"
                :value="$stats['low_stock_products']"
                icon="alert"
                variant="dark"
            />
        </div>
    </div>

    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-lg-6">
            <x-dashboard.widget title="Monthly Revenue" subtitle="Last 12 months">
                <x-dashboard.chart
                    id="monthlyRevenueChart"
                    type="line"
                    :labels="$monthlyRevenue['labels']"
                    :values="$monthlyRevenue['values']"
                    label="Revenue"
                    color="#2563eb"
                    :currency="$stats['currency']"
                />
            </x-dashboard.widget>
        </div>

        <div class="col-lg-6">
            <x-dashboard.widget title="Membership Growth" subtitle="New members per month">
                <x-dashboard.chart
                    id="membershipGrowthChart"
                    type="bar"
                    :labels="$membershipGrowth['labels']"
                    :values="$membershipGrowth['values']"
                    label="New Members"
                    color="#16a34a"
                />
            </x-dashboard.widget>
        </div>
    </div>

    <div class="row g-3 g-xl-4">
        <div class="col-xl-6">
            <x-dashboard.widget title="Recent Members" subtitle="Latest gym registrations">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 sg-dashboard-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentMembers as $member)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $member->name }}</div>
                                        <div class="small text-muted">{{ $member->member_code }}</div>
                                    </td>
                                    <td class="text-nowrap">{{ $member->joined_at->format('M d, Y') }}</td>
                                    <td>
                                        <x-ui.badge :variant="$member->status->badgeVariant()">
                                            {{ $member->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No members yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-dashboard.widget>
        </div>

        <div class="col-xl-6">
            <x-dashboard.widget title="Recent Payments" subtitle="Latest transactions">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 sg-dashboard-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $payment->type->label() }}</div>
                                        <div class="small text-muted">{{ $payment->reference ?? '—' }}</div>
                                    </td>
                                    <td>{{ $payment->member?->name ?? 'Walk-in' }}</td>
                                    <td class="fw-semibold text-nowrap">
                                        {{ App\Support\MoneyFormatter::format($payment->amount, $stats['currency']) }}
                                    </td>
                                    <td class="text-nowrap">{{ $payment->paid_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No payments yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-dashboard.widget>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
