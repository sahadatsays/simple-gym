@props(['summary'])

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h3 class="h6 fw-semibold mb-1">{{ __('members.show.transaction_summary') }}</h3>
                <p class="text-muted small mb-0">{{ __('members.show.transaction_summary_help') }}</p>
            </div>
            <span class="badge text-bg-light">
                {{ trans_choice('members.show.payment_count', $summary->paymentCount, ['count' => $summary->paymentCount]) }}
            </span>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_paid')"
                    :value="App\Support\MoneyFormatter::format($summary->totalPaid, $gymCurrency)"
                    icon="wallet"
                    variant="success"
                    formatted
                />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_renewal_fee')"
                    :value="App\Support\MoneyFormatter::format($summary->totalRenewalFee, $gymCurrency)"
                    icon="user-check"
                    variant="primary"
                    formatted
                />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_pos_paid')"
                    :value="App\Support\MoneyFormatter::format($summary->totalPosPaid, $gymCurrency)"
                    icon="shopping"
                    variant="purple"
                    formatted
                />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_admission_fee')"
                    :value="App\Support\MoneyFormatter::format($summary->totalAdmissionFee, $gymCurrency)"
                    icon="chart"
                    variant="info"
                    formatted
                />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_membership_fee')"
                    :value="App\Support\MoneyFormatter::format($summary->totalMembershipFee, $gymCurrency)"
                    icon="users"
                    variant="dark"
                    formatted
                />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-dashboard.stat-card
                    :title="__('members.show.total_due')"
                    :value="App\Support\MoneyFormatter::format($summary->totalDue, $gymCurrency)"
                    icon="alert"
                    :variant="$summary->totalDue > 0 ? 'danger' : 'success'"
                    formatted
                />
            </div>
        </div>
    </div>
</div>
