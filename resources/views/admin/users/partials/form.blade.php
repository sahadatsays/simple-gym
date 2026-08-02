@php
    $roleOptions = collect($roles)->mapWithKeys(fn (string $role) => [$role => ucwords(str_replace('-', ' ', $role))])->all();
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            label="Full name"
            name="name"
            :value="$user?->name"
            required
        />

        <x-forms.input
            label="Email address"
            name="email"
            type="email"
            :value="$user?->email"
            required
        />

        <x-forms.input
            label="Phone"
            name="phone"
            :value="$user?->phone"
        />

        <x-forms.select
            label="Role"
            name="role"
            :options="$roleOptions"
            :selected="$user?->roles->first()?->name"
            required
        />

        <x-forms.input
            label="{{ $user ? 'New password' : 'Password' }}"
            name="password"
            type="password"
            :required="! $user"
            help="{{ $user ? 'Leave blank to keep the current password.' : null }}"
        />

        <x-forms.input
            label="Confirm password"
            name="password_confirmation"
            type="password"
            :required="! $user"
        />

        <x-forms.checkbox
            label="Active account"
            name="is_active"
            :checked="$user?->is_active ?? true"
        />
    </div>
</div>
