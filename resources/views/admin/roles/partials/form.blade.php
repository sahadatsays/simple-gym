<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            label="Role name"
            name="name"
            placeholder="manager"
            :value="$role?->name"
            required
            help="Use lowercase letters, numbers, and dashes only."
            @if ($isProtected) readonly @endif
        />

        @if ($isProtected)
            <div class="alert alert-info py-2">This is a protected system role. Its name cannot be changed.</div>
        @endif
    </div>
</div>

<div class="mt-2">
    <label class="form-label fw-semibold">Permissions</label>
    <p class="text-muted small">Select the permissions this role should have.</p>

    <x-admin.permission-assignment
        :grouped-permissions="$groupedPermissions"
        :selected="$role?->permissions->pluck('name')->all() ?? []"
    />
</div>
