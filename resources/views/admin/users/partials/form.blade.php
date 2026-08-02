@php
    $roleOptions = collect($roles)->mapWithKeys(fn (string $role) => [$role => ucwords(str_replace('-', ' ', $role))])->all();
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            label="Full name"
            name="name"
            placeholder="John Doe"
            :value="$user?->name"
            required
        />

        <x-forms.input
            label="Username"
            name="username"
            placeholder="johndoe"
            :value="$user?->username"
            required
            help="Letters, numbers, dashes and underscores only."
        />

        <x-forms.input
            label="Email address"
            name="email"
            type="email"
            placeholder="john@example.com"
            :value="$user?->email"
            required
        />

        <x-forms.input
            label="Phone"
            name="phone"
            placeholder="+1 555 000 0000"
            :value="$user?->phone"
        />

        <x-forms.select
            label="Role"
            name="role"
            :options="$roleOptions"
            :selected="$user?->roles->first()?->name"
            required
        />

        @unless ($user)
            <x-forms.input
                label="Password"
                name="password"
                type="password"
                placeholder="Enter a secure password"
                required
            />

            <x-forms.input
                label="Confirm password"
                name="password_confirmation"
                type="password"
                placeholder="Confirm password"
                required
            />
        @endunless

        <x-forms.select
            label="Status"
            name="is_active"
            :options="['1' => 'Active', '0' => 'Inactive']"
            :selected="old('is_active', $user?->is_active ?? true) ? '1' : '0'"
            required
        />
    </div>
</div>
