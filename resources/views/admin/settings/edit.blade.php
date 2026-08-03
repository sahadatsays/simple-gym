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
