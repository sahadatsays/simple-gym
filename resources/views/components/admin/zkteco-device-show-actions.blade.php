@props([
    'device',
])

<div class="d-flex flex-wrap gap-2">
    @can('manage', $device)
        @if ($device->status === App\Enums\ZktecoDeviceStatus::Pending)
            <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                    {{ __('settings.zkteco.approve') }}
                </button>
            </form>
        @endif

        @if ($device->status === App\Enums\ZktecoDeviceStatus::Active)
            <div class="dropdown">
                <button
                    type="button"
                    class="btn btn-primary dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-people me-1" aria-hidden="true"></i>
                    {{ __('settings.zkteco.users_menu') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <button
                            type="button"
                            class="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#syncUserModal"
                        >
                            <i class="bi bi-person-plus me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.sync_user_action') }}
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteUserModal"
                        >
                            <i class="bi bi-person-dash me-2" aria-hidden="true"></i>
                            {{ __('settings.zkteco.delete_user_action') }}
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form
                            action="{{ route('admin.zkteco-devices.clear-users', $device) }}"
                            method="POST"
                            onsubmit="return confirm(@js(__('settings.zkteco.clear_card_users_confirm')))"
                        >
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-people me-2" aria-hidden="true"></i>
                                {{ __('settings.zkteco.clear_card_users') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <div class="dropdown">
                <button
                    type="button"
                    class="btn btn-light dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-hdd-network me-1" aria-hidden="true"></i>
                    {{ __('settings.zkteco.device_menu') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <form
                            action="{{ route('admin.zkteco-devices.reboot', $device) }}"
                            method="POST"
                            onsubmit="return confirm(@js(__('common.confirm.reboot_device')))"
                        >
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>
                                {{ __('settings.zkteco.queue_reboot') }}
                            </button>
                        </form>
                    </li>
                    <li>
                        <form
                            action="{{ route('admin.zkteco-devices.reset-data', $device) }}"
                            method="POST"
                            onsubmit="return confirm(@js(__('settings.zkteco.reset_attendance_data_confirm')))"
                        >
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-journal-x me-2" aria-hidden="true"></i>
                                {{ __('settings.zkteco.reset_attendance_data') }}
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
                                {{ __('settings.zkteco.suspend') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @endif

        @if ($device->status === App\Enums\ZktecoDeviceStatus::Suspended)
            <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                    {{ __('settings.zkteco.reapprove') }}
                </button>
            </form>
        @endif
    @endcan

    <a href="{{ route('admin.zkteco-devices.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        {{ __('settings.zkteco.back_to_devices') }}
    </a>
</div>
