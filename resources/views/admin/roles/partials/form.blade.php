<div class="row g-3">
    <div class="col-md-6">
        <x-forms.input
            :label="__('roles.form.role_name')"
            name="display_name"
            :placeholder="__('roles.form.role_name_placeholder')"
            :value="$role?->display_name"
            required
            :help="__('roles.form.role_name_help')"
        />
    </div>

    <div class="col-md-6">
        <x-forms.input
            :label="__('roles.form.slug')"
            name="slug"
            :placeholder="__('roles.form.slug_placeholder')"
            :value="$role?->name"
            required
            :help="__('roles.form.slug_help')"
            :readonly="$isProtected"
        />
    </div>
</div>

@if ($isProtected)
    <div class="alert alert-info py-2 mb-3">
        {{ __('roles.form.protected_notice') }}
    </div>
@endif

<div class="mt-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <label class="form-label fw-semibold mb-0">{{ __('roles.permissions.title') }}</label>
            <p class="text-muted small mb-0">{{ __('roles.form.permissions_help') }}</p>
        </div>
        @unless ($isProtected)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="sg-permissions-select-all">
                    {{ __('common.actions.select_all') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="sg-permissions-clear-all">
                    {{ __('common.actions.clear_all') }}
                </button>
            </div>
        @endunless
    </div>

    <x-admin.permission-assignment
        :grouped-permissions="$groupedPermissions"
        :selected="$role?->permissions->pluck('name')->all() ?? []"
        :disabled="$isProtected"
    />
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const displayNameInput = document.getElementById('display_name');
                const slugInput = document.getElementById('slug');
                let slugManuallyEdited = {{ old('slug') || ($role?->name ?? '') ? 'true' : 'false' }};

                if (displayNameInput && slugInput && !slugInput.readOnly) {
                    slugInput.addEventListener('input', () => {
                        slugManuallyEdited = true;
                    });

                    displayNameInput.addEventListener('input', () => {
                        if (slugManuallyEdited) {
                            return;
                        }

                        slugInput.value = displayNameInput.value
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    });
                }

                const selectAllButton = document.getElementById('sg-permissions-select-all');
                const clearAllButton = document.getElementById('sg-permissions-clear-all');

                selectAllButton?.addEventListener('click', () => {
                    document.querySelectorAll('.sg-permission-checkbox:not(:disabled)').forEach((checkbox) => {
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change'));
                    });
                });

                clearAllButton?.addEventListener('click', () => {
                    document.querySelectorAll('.sg-permission-checkbox:not(:disabled)').forEach((checkbox) => {
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change'));
                    });
                });
            })();
        </script>
    @endpush
@endonce
