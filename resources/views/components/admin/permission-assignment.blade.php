@props([
    'groupedPermissions',
    'selected' => [],
    'disabled' => false,
])

<div class="sg-permission-groups">
    @foreach ($groupedPermissions as $group => $permissions)
        <div class="card border mb-3">
            <div class="card-header bg-light py-2">
                <div class="form-check mb-0">
                    <input
                        type="checkbox"
                        class="form-check-input sg-permission-group-toggle"
                        id="group-{{ $group }}"
                        @disabled($disabled)
                    >
                    <label class="form-check-label fw-semibold text-capitalize" for="group-{{ $group }}">
                        {{ str_replace(['-', '_'], ' ', $group) }}
                    </label>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    @foreach ($permissions as $permission)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    id="permission-{{ md5($permission) }}"
                                    @class(['form-check-input', 'sg-permission-checkbox'])
                                    data-group="group-{{ $group }}"
                                    @checked(in_array($permission, old('permissions', $selected), true))
                                    @disabled($disabled)
                                >
                                <label class="form-check-label small" for="permission-{{ md5($permission) }}">
                                    {{ $permission }}
                                </label>
                            </div>
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
