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
    >
        <i class="bi bi-three-dots" aria-hidden="true"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm sg-dropdown-menu">
        @can('view', $device)
            <li>
                <a class="dropdown-item" href="{{ route('admin.zkteco-devices.show', $device) }}">
                    View device
                </a>
            </li>
        @endcan

        @can('manage', $device)
            @if ($device->status === App\Enums\ZktecoDeviceStatus::Pending)
                <li>
                    <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">Approve device</button>
                    </form>
                </li>
            @endif

            @if ($device->status === App\Enums\ZktecoDeviceStatus::Active)
                <li>
                    <form action="{{ route('admin.zkteco-devices.suspend', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">Suspend device</button>
                    </form>
                </li>
            @endif

            @if ($device->status === App\Enums\ZktecoDeviceStatus::Suspended)
                <li>
                    <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="dropdown-item">Re-approve device</button>
                    </form>
                </li>
            @endif
        @endcan
    </ul>
</div>
