@extends('layouts.admin', ['heading' => 'Renew Membership'])

@section('title', 'Renew '.$member->name)

@section('content')
    <x-ui.page-header :title="'Renew: '.$member->name" :subtitle="$member->member_code">
        <x-slot:actions>
            <a href="{{ route('admin.members.renew.create') }}" class="btn btn-light">Back to Search</a>
            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-light">View Profile</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Current Membership</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-5">Plan</dt>
                        <dd class="col-7">{{ $member->membershipPlan?->name ?? '—' }}</dd>

                        <dt class="col-5">Expires</dt>
                        <dd class="col-7">
                            {{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}
                        </dd>

                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @if ($member->isActive())
                                <span class="sg-status-badge sg-status-badge-active">Active</span>
                            @else
                                <span class="sg-status-badge sg-status-badge-inactive">
                                    {{ $member->status->label() }}
                                </span>
                            @endif
                        </dd>
                    </dl>

                    <div class="alert alert-light border mt-3 mb-0 small">
                        @if ($member->isActive())
                            Renewal extends from the current expiry date.
                        @else
                            Renewal starts from today because membership has expired.
                        @endif
                    </div>
                </div>
            </div>

            @if ($renewals->isNotEmpty())
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h2 class="h6 fw-semibold mb-3">Recent Renewals</h2>
                        <ul class="list-unstyled mb-0 small">
                            @foreach ($renewals as $renewal)
                                <li @class(['pb-3 mb-3 border-bottom' => ! $loop->last])>
                                    <div class="fw-semibold">{{ $renewal->membershipPlan->name }}</div>
                                    <div class="text-muted">
                                        {{ $renewal->renewed_at->format('M j, Y') }}
                                    </div>
                                    <div class="text-muted">
                                        {{ $renewal->previous_expires_at?->format('M j, Y') ?? '—' }}
                                        →
                                        {{ $renewal->new_expires_at->format('M j, Y') }}
                                    </div>
                                    @if ($renewal->invoice?->payment)
                                        <a
                                            href="{{ route('admin.members.receipt', [$member, $renewal->invoice]) }}"
                                            class="small"
                                        >
                                            {{ $renewal->invoice->payment->receipt_number }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <x-ui.card>
                <form
                    action="{{ route('admin.members.renew.store', $member) }}"
                    method="POST"
                    x-data="membershipRenewal({
                        plans: @js($plans->map(fn ($plan) => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'duration_days' => $plan->duration_days,
                            'membership_fee' => (float) $plan->membership_fee,
                        ])->values()->all()),
                        memberIsActive: @js($member->isActive()),
                        currentExpiry: @js($member->membership_expires_at?->format('Y-m-d')),
                        selectedPlanId: @js(old('membership_plan_id', $member->membership_plan_id)),
                        amountReceived: @js(old('amount_received')),
                    })"
                >
                    @csrf

                    <h2 class="h6 fw-semibold mb-3">1. Select Plan</h2>
                    <div class="row">
                        <div class="col-md-8">
                            <label for="membership_plan_id" class="form-label">
                                Plan <span class="text-danger">*</span>
                            </label>
                            <select
                                name="membership_plan_id"
                                id="membership_plan_id"
                                x-model="selectedPlanId"
                                @change="syncAmount()"
                                @class(['form-select', 'is-invalid' => $errors->has('membership_plan_id')])
                                required
                            >
                                <option value="">Select a plan</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('membership_plan_id', $member->membership_plan_id) == $plan->id)>
                                        {{ $plan->name }} ({{ $plan->duration_days }} days)
                                    </option>
                                @endforeach
                            </select>
                            @error('membership_plan_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card border-0 bg-light mt-3" x-show="selectedPlan" x-cloak>
                        <div class="card-body">
                            <h3 class="h6 fw-semibold mb-3">2. Charge Summary</h3>
                            <div class="d-flex justify-content-between small mb-2">
                                <span>Membership Renewal Fee</span>
                                <span x-text="selectedPlan ? formatMoney(selectedPlan.membership_fee) : ''"></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-semibold mb-3">
                                <span>Total Due</span>
                                <span x-text="selectedPlan ? formatMoney(totalDue) : ''"></span>
                            </div>

                            <h3 class="h6 fw-semibold mb-2">3. New Expiry</h3>
                            <p class="small text-muted mb-0" x-text="expiryExplanation"></p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 fw-semibold mb-3">4. Receive Payment</h2>
                    <div class="row">
                        <div class="col-md-4">
                            <x-forms.select
                                label="Payment method"
                                name="payment_method"
                                :options="[
                                    'cash' => 'Cash',
                                    'card' => 'Card',
                                    'mobile_banking' => 'Mobile Banking',
                                ]"
                                :selected="old('payment_method', 'cash')"
                                required
                            />
                        </div>
                        <div class="col-md-4">
                            <x-forms.input
                                label="Reference"
                                name="payment_reference"
                                :value="old('payment_reference')"
                                placeholder="Optional"
                            />
                        </div>
                        <div class="col-md-4">
                            <label for="amount_received" class="form-label">
                                Amount received <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                name="amount_received"
                                id="amount_received"
                                x-model="amountReceived"
                                step="0.01"
                                min="0"
                                @class(['form-control', 'is-invalid' => $errors->has('amount_received')])
                                required
                            >
                            @error('amount_received')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <x-ui.button type="submit">Complete Renewal</x-ui.button>
                        <a href="{{ route('admin.members.renew.create') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('membershipRenewal', (config) => ({
                plans: config.plans,
                memberIsActive: config.memberIsActive,
                currentExpiry: config.currentExpiry,
                selectedPlanId: config.selectedPlanId ? String(config.selectedPlanId) : '',
                amountReceived: config.amountReceived ?? '',
                currencySymbol: @js(App\Support\MoneyFormatter::symbol($gymCurrency)),

                init() {
                    this.syncAmount();
                },

                get selectedPlan() {
                    return this.plans.find((plan) => String(plan.id) === String(this.selectedPlanId)) ?? null;
                },

                get totalDue() {
                    return this.selectedPlan ? this.selectedPlan.membership_fee : 0;
                },

                get expiryExplanation() {
                    if (! this.selectedPlan) {
                        return '';
                    }

                    const baseLabel = this.memberIsActive && this.currentExpiry
                        ? `Current expiry (${this.formatDate(this.currentExpiry)}) + ${this.selectedPlan.duration_days} days`
                        : `Today + ${this.selectedPlan.duration_days} days`;

                    return baseLabel;
                },

                syncAmount() {
                    if (this.selectedPlan) {
                        this.amountReceived = Number(this.totalDue).toFixed(2);
                    }
                },

                formatMoney(amount) {
                    return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                formatDate(value) {
                    return new Date(value + 'T00:00:00').toLocaleDateString(undefined, {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
                },
            }));
        });
    </script>
@endpush
