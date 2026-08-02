@extends('layouts.admin', ['heading' => 'Gym Settings'])

@section('title', 'Gym Settings')

@section('content')
    <x-ui.page-header
        title="Gym Settings"
        subtitle="Configure your gym profile and operating details"
    />

    <x-ui.card>
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <x-forms.input
                        label="Gym name"
                        name="name"
                        :value="$settings->name"
                        required
                    />

                    <x-forms.input
                        label="Email"
                        name="email"
                        type="email"
                        :value="$settings->email"
                    />

                    <x-forms.input
                        label="Phone"
                        name="phone"
                        :value="$settings->phone"
                    />

                    <x-forms.textarea
                        label="Address"
                        name="address"
                        :value="$settings->address"
                    />

                    <x-forms.select
                        label="Timezone"
                        name="timezone"
                        :options="collect($timezones)->mapWithKeys(fn ($tz) => [$tz => $tz])->all()"
                        :selected="$settings->timezone"
                        required
                    />

                    <x-forms.select
                        label="Currency"
                        name="currency"
                        :options="$currencies"
                        :selected="$settings->currency"
                        placeholder="Select currency"
                        required
                    />

                    <div class="row">
                        <div class="col-md-6">
                            <x-forms.input
                                label="Opening time"
                                name="opening_time"
                                type="time"
                                :value="optional($settings->opening_time)?->format('H:i')"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.input
                                label="Closing time"
                                name="closing_time"
                                type="time"
                                :value="optional($settings->closing_time)?->format('H:i')"
                            />
                        </div>
                    </div>

                    <x-forms.checkbox
                        label="Gym is open"
                        name="is_open"
                        :checked="$settings->is_open"
                    />
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-ui.button type="submit">Save Settings</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
