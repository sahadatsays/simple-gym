@extends('layouts.admin', ['heading' => 'Register Member'])

@section('title', 'Register Member')

@section('content')
    <x-ui.page-header
        title="Register Member"
        subtitle="Create member, collect payment, activate membership, and assign RFID"
    />

    <x-ui.card>
        <form
            action="{{ route('admin.members.register.store') }}"
            method="POST"
            enctype="multipart/form-data"
            x-data="memberRegistration({
                plans: @js($plans->map(fn ($plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_days' => $plan->duration_days,
                    'admission_fee' => (float) $plan->admission_fee,
                    'membership_fee' => (float) $plan->membership_fee,
                ])->values()->all()),
                selectedPlanId: @js(old('membership_plan_id')),
                amountReceived: @js(old('amount_received')),
            })"
        >
            @csrf

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h6 fw-semibold mb-3">Photo</h2>
                            <input
                                type="file"
                                name="photo"
                                id="photo"
                                accept="image/jpeg,image/png,image/webp"
                                @class(['form-control', 'is-invalid' => $errors->has('photo')])
                            >
                            @error('photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">JPG, PNG or WebP. Max 2 MB.</div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="small text-muted">Member ID</div>
                        <div class="fw-semibold">{{ $nextMemberCode }}</div>
                        <div class="form-text mb-0">Assigned automatically on registration.</div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <h2 class="h6 fw-semibold mb-3">1. Member Details</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <x-forms.input label="Full name" name="name" :value="old('name')" required />
                        </div>
                        <div class="col-md-6">
                            <x-forms.input label="Phone" name="phone" :value="old('phone')" required />
                        </div>
                        <div class="col-md-6">
                            <x-forms.input label="Email" name="email" type="email" :value="old('email')" />
                        </div>
                        <div class="col-md-6">
                            <x-forms.select
                                label="Gender"
                                name="gender"
                                :options="App\Enums\Gender::options()"
                                :selected="old('gender')"
                                placeholder="Select gender"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.date-picker
                                label="Date of birth"
                                name="date_of_birth"
                                :value="old('date_of_birth')"
                                max-date="today"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.date-picker
                                label="Join date"
                                name="joined_at"
                                :value="old('joined_at', now()->format('Y-m-d'))"
                                required
                            />
                        </div>
                        <div class="col-12">
                            <x-forms.textarea label="Address" name="address" rows="2" :value="old('address')" />
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 fw-semibold mb-3">Emergency Contact</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <x-forms.input label="Contact name" name="emergency_contact_name" :value="old('emergency_contact_name')" />
                        </div>
                        <div class="col-md-6">
                            <x-forms.input label="Contact phone" name="emergency_contact_phone" :value="old('emergency_contact_phone')" />
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 fw-semibold mb-3">2. Membership Plan</h2>
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
                                    <option value="{{ $plan->id }}" @selected(old('membership_plan_id') == $plan->id)>
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
                            <h3 class="h6 fw-semibold mb-3">3. Charge Summary</h3>
                            <template x-if="selectedPlan && selectedPlan.admission_fee > 0">
                                <div class="d-flex justify-content-between small mb-2">
                                    <span>Admission Fee</span>
                                    <span x-text="formatMoney(selectedPlan.admission_fee)"></span>
                                </div>
                            </template>
                            <div class="d-flex justify-content-between small mb-2">
                                <span>Membership Fee</span>
                                <span x-text="selectedPlan ? formatMoney(selectedPlan.membership_fee) : ''"></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-semibold">
                                <span>Total Due</span>
                                <span x-text="selectedPlan ? formatMoney(totalDue) : ''"></span>
                            </div>
                            <div class="form-text mt-2 mb-0" x-show="selectedPlan">
                                Membership expires <span x-text="expiryLabel"></span> after join date.
                            </div>
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

                    <hr class="my-4">

                    <h2 class="h6 fw-semibold mb-3">5. Assign RFID (optional)</h2>
                    @if ($unassignedCards->isEmpty())
                        <p class="text-muted small mb-0">
                            No unassigned RFID cards available.
                            <a href="{{ route('admin.rfid-cards.index') }}">Register a card</a> first.
                        </p>
                    @else
                        <x-forms.searchable-select
                            label="RFID card"
                            name="rfid_card_id"
                            id="registration-rfid-card"
                            :options="$unassignedCards->mapWithKeys(fn ($card) => [$card->id => $card->card_number])->all()"
                            :selected="old('rfid_card_id')"
                            placeholder="Search card number..."
                        />
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <x-ui.button type="submit">Complete Registration</x-ui.button>
                <a href="{{ route('admin.members.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </x-ui.card>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('memberRegistration', (config) => ({
                plans: config.plans,
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
                    if (! this.selectedPlan) {
                        return 0;
                    }

                    return this.selectedPlan.admission_fee + this.selectedPlan.membership_fee;
                },

                get expiryLabel() {
                    if (! this.selectedPlan) {
                        return '';
                    }

                    return `${this.selectedPlan.duration_days} days`;
                },

                syncAmount() {
                    if (this.selectedPlan) {
                        this.amountReceived = this.totalDue.toFixed(2);
                    }
                },

                formatMoney(amount) {
                    return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
            }));
        });
    </script>
@endpush
