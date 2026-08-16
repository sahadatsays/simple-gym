@props([
    'groupedPermissions',
    'selected' => [],
    'disabled' => false,
])

<div class="sg-permission-grid">
    @foreach ($groupedPermissions as $group => $permissions)
        <div class="sg-permission-group card border-0 shadow-sm">
            <div class="sg-permission-group__header">
                <div class="form-check mb-0">
                    <input
                        type="checkbox"
                        class="form-check-input sg-permission-group-toggle"
                        id="group-{{ $group }}"
                        @disabled($disabled)
                    >
                    <label class="form-check-label fw-semibold" for="group-{{ $group }}">
                        {{ \App\Support\PermissionRegistry::groupLabel($group) }}
                    </label>
                </div>
                <span class="badge text-bg-light">{{ count($permissions) }}</span>
            </div>

            <div class="sg-permission-group__body">
                <div class="row g-2">
                    @foreach ($permissions as $permission)
                        <div class="col-sm-6 col-xl-4">
                            <label class="sg-permission-item" for="permission-{{ md5($permission) }}">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    id="permission-{{ md5($permission) }}"
                                    @class(['sg-permission-checkbox'])
                                    data-group="group-{{ $group }}"
                                    @checked(in_array($permission, old('permissions', $selected), true))
                                    @disabled($disabled)
                                >
                                <span class="sg-permission-item__content">
                                    <span class="sg-permission-item__label">
                                        {{ \App\Support\PermissionRegistry::permissionLabel($permission) }}
                                    </span>
                                    <span class="sg-permission-item__slug">{{ $permission }}</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('.sg-permission-group-toggle').forEach((groupToggle) => {
                const groupId = groupToggle.id;

                const syncGroupState = () => {
                    const boxes = document.querySelectorAll(`.sg-permission-checkbox[data-group="${groupId}"]`);
                    const checkedCount = [...boxes].filter((box) => box.checked).length;
                    groupToggle.checked = checkedCount > 0 && checkedCount === boxes.length;
                    groupToggle.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
                };

                groupToggle.addEventListener('change', () => {
                    document.querySelectorAll(`.sg-permission-checkbox[data-group="${groupId}"]`)
                        .forEach((box) => {
                            box.checked = groupToggle.checked;
                        });
                });

                document.querySelectorAll(`.sg-permission-checkbox[data-group="${groupId}"]`)
                    .forEach((box) => box.addEventListener('change', syncGroupState));

                syncGroupState();
            });
        </script>
    @endpush
@endonce
