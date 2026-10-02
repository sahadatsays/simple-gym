@extends('layouts.admin', ['heading' => 'Gym Settings'])

@section('title', 'Gym Settings')

@section('content')
    <x-ui.page-header
        title="Gym Settings"
        subtitle="Manage your gym profile, billing defaults, and receipt preferences"
    />

    @unless ($canUpdate)
        <div class="alert alert-info">
            You can view these settings, but only authorized users can make changes.
        </div>
    @endunless

    <form
        action="{{ route('admin.settings.update') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">
                <x-ui.card title="Gym Profile" class="mb-4">
                    <x-forms.input
                        label="Gym name"
                        name="name"
                        :value="$settings->name"
                        required
                        :disabled="! $canUpdate"
                    />

                    <div class="mb-4">
                        <label class="form-label">Logo</label>
                        <div class="d-flex flex-wrap align-items-start gap-4">
                            <div class="sg-settings-logo-preview border rounded bg-light d-flex align-items-center justify-content-center">
                                @if ($settings->logo_url)
                                    <img src="{{ $settings->logo_url }}" alt="{{ $settings->name }} logo" class="img-fluid">
                                @else
                                    <span class="text-muted small px-3 text-center">No logo uploaded</span>
                                @endif
                            </div>

                            @if ($canUpdate)
                                <div class="flex-grow-1">
                                    <input
                                        type="file"
                                        name="logo"
                                        id="logo"
                                        accept="image/jpeg,image/png,image/webp"
                                        @class(['form-control', 'is-invalid' => $errors->has('logo')])
                                    >
                                    <div class="form-text">PNG, JPG, or WebP up to 2 MB. Recommended square image.</div>
                                    @error('logo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    @if ($settings->logo_path)
                                        <x-forms.checkbox
                                            label="Remove current logo"
                                            name="remove_logo"
                                            :checked="old('remove_logo', false)"
                                            class="mt-3"
                                        />
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <x-forms.textarea
                        label="Address"
                        name="address"
                        rows="3"
                        :value="$settings->address"
                        :disabled="! $canUpdate"
                    />

                    <x-forms.input
                        label="Phone"
                        name="phone"
                        :value="$settings->phone"
                        :disabled="! $canUpdate"
                    />
                </x-ui.card>

                <x-ui.card title="Billing & Payments" class="mb-4">
                    <x-forms.select
                        label="Currency"
                        name="currency"
                        :options="$currencies"
                        :selected="$settings->currency"
                        placeholder="Select currency"
                        required
                        :disabled="! $canUpdate"
                    />

                    <x-forms.money-input
                        label="Default admission fee"
                        name="default_admission_fee"
                        :value="$settings->default_admission_fee"
                        help="Used as the default when creating new membership plans."
                        required
                        :disabled="! $canUpdate"
                    />

                    <x-forms.money-input
                        label="Card fee"
                        name="rfid_card_fee"
                        :value="$settings->rfid_card_fee"
                        help="Charged with the deposit when a member is issued a new RFID card."
                        required
                        :disabled="! $canUpdate"
                    />

                    <x-forms.money-input
                        label="Card deposit"
                        name="rfid_card_deposit"
                        :value="$settings->rfid_card_deposit"
                        help="Collected with the card fee when a member is issued a new RFID card."
                        required
                        :disabled="! $canUpdate"
                    />

                    <x-forms.money-input
                        label="Replacement card fee"
                        name="rfid_replacement_card_fee"
                        :value="$settings->rfid_replacement_card_fee"
                        help="Charged when a member's card is replaced. Leave at zero to replace without a charge."
                        required
                        :disabled="! $canUpdate"
                    />

                    <div class="mb-3">
                        <label class="form-label">
                            Payment methods
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            @php
                                $selectedMethods = old('enabled_payment_methods', $settings->enabledPaymentMethodValues());
                            @endphp
                            @foreach ($paymentMethods as $value => $label)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            name="enabled_payment_methods[]"
                                            id="payment_method_{{ $value }}"
                                            value="{{ $value }}"
                                            class="form-check-input"
                                            @checked(in_array($value, $selectedMethods, true))
                                            @disabled(! $canUpdate)
                                        >
                                        <label class="form-check-label" for="payment_method_{{ $value }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('enabled_payment_methods')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('enabled_payment_methods.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </x-ui.card>

                <x-ui.card title="{{ __('settings.sections.device_access_restriction') }}" class="mb-4">
                    <p class="text-muted small">
                        {{ __('settings.device_restriction.description') }}
                    </p>

                    <div
                        class="border rounded-3 p-3 mb-3 bg-light"
                        x-data="restrictionCountdown(@js($restrictionStatus))"
                        x-init="start()"
                    >
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <strong class="small">{{ __('settings.device_restriction.status_title') }}</strong>
                            <span class="badge" :class="badgeClass" x-text="badgeLabel"></span>
                        </div>

                        <p class="small mb-2" x-text="statusMessage"></p>

                        <dl class="row small mb-0">
                            <dt class="col-sm-4">{{ __('settings.device_restriction.configured_window') }}</dt>
                            <dd class="col-sm-8 mb-1">
                                <span x-text="startTime || '—'"></span>
                                –
                                <span x-text="endTime || '—'"></span>
                                <span class="text-muted">(<span x-text="timezone"></span>)</span>
                            </dd>

                            <template x-if="active">
                                <dt class="col-sm-4">{{ __('settings.device_restriction.remaining') }}</dt>
                            </template>
                            <template x-if="active">
                                <dd class="col-sm-8 mb-0 fw-semibold text-danger" x-text="remainingLabel"></dd>
                            </template>
                        </dl>

                        <p class="text-muted small mb-0 mt-2">
                            {{ __('settings.device_restriction.cards_restored_hint') }}
                        </p>
                    </div>

                    <x-forms.checkbox
                        label="{{ __('settings.device_restriction.enable') }}"
                        name="member_access_restriction_enabled"
                        :checked="old('member_access_restriction_enabled', $settings->member_access_restriction_enabled)"
                        :disabled="! $canUpdate"
                    />

                    <x-forms.select
                        label="{{ __('settings.device_restriction.restricted_group') }}"
                        name="member_access_restriction_group"
                        :options="$restrictionGroups"
                        :selected="old('member_access_restriction_group', $settings->member_access_restriction_group?->value ?? 'male')"
                        :disabled="! $canUpdate"
                    />

                    <div class="row">
                        <div class="col-md-6">
                            <x-forms.time-picker
                                label="{{ __('settings.device_restriction.start_time') }}"
                                name="member_access_restriction_start_time"
                                :value="old('member_access_restriction_start_time', optional($settings->member_access_restriction_start_time)?->format('H:i'))"
                                :disabled="! $canUpdate"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.time-picker
                                label="{{ __('settings.device_restriction.end_time') }}"
                                name="member_access_restriction_end_time"
                                :value="old('member_access_restriction_end_time', optional($settings->member_access_restriction_end_time)?->format('H:i'))"
                                help="{{ __('settings.device_restriction.overnight_help') }}"
                                :disabled="! $canUpdate"
                            />
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Receipts & Membership" class="mb-4">
                    <x-forms.textarea
                        label="Receipt footer"
                        name="receipt_footer"
                        rows="3"
                        placeholder="Thank you for your payment..."
                        :value="$settings->receipt_footer"
                        help="Shown at the bottom of printed and PDF receipts."
                        :disabled="! $canUpdate"
                    />

                    <x-forms.input
                        label="Membership reminder days"
                        name="membership_reminder_days"
                        type="number"
                        min="1"
                        max="365"
                        :value="$settings->membership_reminder_days"
                        help="How many days before expiry members should be reminded."
                        required
                        :disabled="! $canUpdate"
                    />
                </x-ui.card>
            </div>

            <div class="col-xl-4">
                <x-ui.card title="Operating Details">
                    <x-forms.input
                        label="Email"
                        name="email"
                        type="email"
                        :value="$settings->email"
                        :disabled="! $canUpdate"
                    />

                    <x-forms.select
                        label="Timezone"
                        name="timezone"
                        :options="collect($timezones)->mapWithKeys(fn ($tz) => [$tz => $tz])->all()"
                        :selected="$settings->timezone"
                        required
                        :disabled="! $canUpdate"
                    />

                    <div class="row">
                        <div class="col-md-6">
                            <x-forms.time-picker
                                label="Opening time"
                                name="opening_time"
                                :value="optional($settings->opening_time)?->format('H:i')"
                                :disabled="! $canUpdate"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.time-picker
                                label="Closing time"
                                name="closing_time"
                                :value="optional($settings->closing_time)?->format('H:i')"
                                :disabled="! $canUpdate"
                            />
                        </div>
                    </div>

                    <x-forms.checkbox
                        label="Gym is open"
                        name="is_open"
                        :checked="$settings->is_open"
                        :disabled="! $canUpdate"
                    />
                </x-ui.card>

                @if ($canUpdate)
                    <div class="d-grid mt-4">
                        <x-ui.button type="submit">Save Settings</x-ui.button>
                    </div>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('restrictionCountdown', (status) => ({
                enabled: Boolean(status.enabled),
                active: Boolean(status.active),
                startTime: status.start_time,
                endTime: status.end_time,
                timezone: status.timezone,
                periodStartMs: status.period_start_at ? new Date(status.period_start_at).getTime() : null,
                periodEndMs: status.period_end_at ? new Date(status.period_end_at).getTime() : null,
                secondsRemaining: status.seconds_remaining,
                timer: null,
                messages: {
                    disabled: @js(__('settings.device_restriction.status_disabled')),
                    inactive: @js(__('settings.device_restriction.status_inactive')),
                    active: @js(__('settings.device_restriction.status_active')),
                },

                start() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },

                tick() {
                    if (! this.enabled || this.periodStartMs === null || this.periodEndMs === null) {
                        this.active = false;
                        this.secondsRemaining = null;

                        return;
                    }

                    const nowMs = Date.now();
                    this.active = nowMs >= this.periodStartMs && nowMs < this.periodEndMs;
                    this.secondsRemaining = this.active
                        ? Math.max(0, Math.floor((this.periodEndMs - nowMs) / 1000))
                        : null;
                },

                get remainingLabel() {
                    if (this.secondsRemaining === null) {
                        return '';
                    }

                    const hours = Math.floor(this.secondsRemaining / 3600);
                    const minutes = Math.floor((this.secondsRemaining % 3600) / 60);
                    const seconds = this.secondsRemaining % 60;
                    const parts = [];

                    if (hours > 0) {
                        parts.push(`${hours} ${hours === 1 ? 'hour' : 'hours'}`);
                    }

                    parts.push(`${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`);
                    parts.push(`${seconds} ${seconds === 1 ? 'second' : 'seconds'} remaining`);

                    return parts.join(' ');
                },

                get statusMessage() {
                    if (! this.enabled) {
                        return this.messages.disabled;
                    }

                    return this.active ? this.messages.active : this.messages.inactive;
                },

                get badgeLabel() {
                    if (! this.enabled) {
                        return 'Disabled';
                    }

                    return this.active ? 'Active' : 'Inactive';
                },

                get badgeClass() {
                    if (! this.enabled) {
                        return 'text-bg-secondary';
                    }

                    return this.active ? 'text-bg-danger' : 'text-bg-success';
                },
            }));
        });
    </script>
@endpush

@push('styles')
    <style>
        .sg-settings-logo-preview {
            width: 120px;
            height: 120px;
            overflow: hidden;
        }

        .sg-settings-logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
@endpush
