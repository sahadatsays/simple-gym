@props([
    'device',
])

<div class="dropdown sg-table-dropdown">
    <button
        type="button"
        class="btn btn-sm btn-outline-secondary sg-action-btn"
        data-bs-toggle="dropdown"
        data-bs-display="static"
        data-bs-popper-config='{"strategy":"fixed","modifiers":[{"name":"offset","options":{"offset":[0,8]}}]}'
        aria-expanded="false"
        aria-label="{{ __('common.table.actions') }}"
    >
        <i class="bi bi-three-dots" aria-hidden="true"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm sg-dropdown-menu">
        @can('view', $device)
            <li>
                <a class="dropdown-item" href="{{ route('admin.zkteco-devices.show', $device) }}">
                    <i class="bi bi-eye me-2" aria-hidden="true"></i>
                    {{ __('common.actions.view_details') }}
                </a>
            </li>
        @endcan

        @can('manage', $device)
            @if ($device->status === App\Enums\ZktecoDeviceStatus::Pending)
                <li>
                    <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.approve_device') }}
                        </button>
                    </form>
                </li>
            @endif

            @if ($device->status === App\Enums\ZktecoDeviceStatus::Active)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('admin.zkteco-devices.reboot', $device) }}" method="POST" onsubmit="return confirm(@js(__('common.confirm.reboot_device')))">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.queue_reboot') }}
                        </button>
                    </form>
                </li>
                <li>
                    <form action="{{ route('admin.zkteco-devices.reset-data', $device) }}" method="POST" onsubmit="return confirm(@js(__('settings.zkteco.reset_attendance_data_confirm')))">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-journal-x me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.reset_attendance_data') }}
                        </button>
                    </form>
                </li>
                <li>
                    <form action="{{ route('admin.zkteco-devices.clear-users', $device) }}" method="POST" onsubmit="return confirm(@js(__('settings.zkteco.clear_card_users_confirm')))">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-people me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.clear_card_users') }}
                        </button>
                    </form>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('admin.zkteco-devices.suspend', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-pause-circle me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.suspend_device') }}
                        </button>
                    </form>
                </li>
            @endif

            @if ($device->status === App\Enums\ZktecoDeviceStatus::Suspended)
                <li>
                    <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.reapprove_device') }}
                        </button>
                    </form>
                </li>
            @endif
        @endcan
    </ul>
</div>
