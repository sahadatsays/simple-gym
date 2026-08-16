@php
    $roleOptions = collect($roles)->mapWithKeys(
        fn (string $role): array => [$role => __('roles.display_names.'.str_replace('-', '_', $role))]
    )->all();
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            :label="__('common.fields.full_name')"
            name="name"
            :placeholder="__('common.placeholders.name_example')"
            :value="$user?->name"
            required
        />

        <x-forms.input
            :label="__('common.fields.username')"
            name="username"
            :placeholder="__('common.placeholders.username_example')"
            :value="$user?->username"
            required
            :help="__('users.form.username_help')"
        />

        <x-forms.input
            :label="__('common.fields.email_address')"
            name="email"
            type="email"
            placeholder="john@example.com"
            :value="$user?->email"
            required
        />

        <x-forms.input
            :label="__('common.fields.phone')"
            name="phone"
            :placeholder="__('common.placeholders.phone_example')"
            :value="$user?->phone"
        />

        <x-forms.select
            :label="__('common.table.role')"
            name="role"
            :options="$roleOptions"
            :selected="$user?->roles->first()?->name"
            required
        />

        @unless ($user)
            <x-forms.input
                :label="__('common.fields.password')"
                name="password"
                type="password"
                :placeholder="__('common.placeholders.secure_password')"
                required
            />

            <x-forms.input
                :label="__('common.fields.confirm_password')"
                name="password_confirmation"
                type="password"
                :placeholder="__('common.placeholders.confirm_new_password')"
                required
            />
        @endunless

        <x-forms.select
            :label="__('common.filters.status')"
            name="is_active"
            :options="['1' => __('common.status.active'), '0' => __('common.status.inactive')]"
            :selected="old('is_active', $user?->is_active ?? true) ? '1' : '0'"
            required
        />
    </div>
</div>
