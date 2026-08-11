@extends('layouts.admin', ['heading' => 'Device Details'])

@section('title', $device->serial_number)

@section('content')
    <x-ui.page-header :title="$device->serial_number" subtitle="ZKTeco attendance device">
        <x-slot:actions>
            <div class="d-flex flex-wrap gap-2">
                @can('manage', $device)
                    @if ($device->status === App\Enums\ZktecoDeviceStatus::Pending)
                        <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary">Approve Device</button>
                        </form>
                    @endif

                    @if ($device->status === App\Enums\ZktecoDeviceStatus::Active)
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncUserModal">
                            Sync User
                        </button>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                            Delete User
                        </button>
                        <form action="{{ route('admin.zkteco-devices.restart', $device) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-light">Queue Restart</button>
                        </form>
                        <form action="{{ route('admin.zkteco-devices.reboot', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Queue a reboot for this device?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Queue Reboot</button>
                        </form>
                        <form action="{{ route('admin.zkteco-devices.suspend', $device) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-secondary">Suspend</button>
                        </form>
                    @endif

                    @if ($device->status === App\Enums\ZktecoDeviceStatus::Suspended)
                        <form action="{{ route('admin.zkteco-devices.approve', $device) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary">Re-approve Device</button>
                        </form>
                    @endif
                @endcan

                <a href="{{ route('admin.zkteco-devices.index') }}" class="btn btn-light">Back to Devices</a>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($device->status === App\Enums\ZktecoDeviceStatus::Pending)
        <div class="alert alert-warning">
            This device is pending approval. Attendance uploads are held until you approve it.
        </div>
    @endif

    @if ($device->status === App\Enums\ZktecoDeviceStatus::Suspended)
        <div class="alert alert-secondary">
            This device is suspended. Approve it again to resume data sync and command delivery.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Device Overview</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            <span class="sg-status-badge {{ $device->status->badgeClass() }}">
                                {{ $device->status->label() }}
                            </span>
                        </dd>

                        <dt class="col-5">Protocol</dt>
                        <dd class="col-7">{{ $device->protocol_generation }}</dd>

                        <dt class="col-5">Last seen</dt>
                        <dd class="col-7">
                            {{ $device->last_seen_at?->format('M j, Y g:i A') ?? 'Never' }}
                        </dd>

                        <dt class="col-5">Registered</dt>
                        <dd class="col-7">{{ $device->created_at->format('M j, Y') }}</dd>
                    </dl>
                </div>
            </div>

            @if ($device->stamps)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-3">Sync Stamps</h3>
                        <dl class="row sg-profile-list mb-0">
                            @foreach ($device->stamps as $table => $stamp)
                                <dt class="col-5">{{ $table }}</dt>
                                <dd class="col-7 text-break">{{ $stamp }}</dd>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @endif

            @if ($device->capabilities)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-3">Capabilities</h3>
                        <dl class="row sg-profile-list mb-0">
                            @foreach ($device->capabilities as $key => $value)
                                <dt class="col-5">{{ $key }}</dt>
                                <dd class="col-7 text-break">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom">
                        <h3 class="h6 fw-semibold mb-0">Command Queue</h3>
                        <p class="text-muted small mb-0">Commands are delivered when the device polls `/iclock/push`.</p>
                    </div>
                    <div class="table-responsive sg-data-table-wrapper">
                        <table class="table table-hover align-middle mb-0 sg-data-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Command</th>
                                    <th>Status</th>
                                    <th class="d-none d-md-table-cell">Sent</th>
                                    <th class="d-none d-lg-table-cell">Acknowledged</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($device->commands as $command)
                                    <tr>
                                        <td class="ps-4">
                                            <code class="small">{{ $command->command }}</code>
                                        </td>
                                        <td>
                                            @php
                                                $commandBadge = match ($command->status) {
                                                    'completed', 'acknowledged' => 'sg-status-badge-active',
                                                    'failed' => 'sg-status-badge-inactive',
                                                    'sent' => 'sg-status-badge-warning',
                                                    default => 'sg-status-badge-inactive',
                                                };
                                            @endphp
                                            <span class="sg-status-badge {{ $commandBadge }}">
                                                {{ ucfirst($command->status) }}
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted small">
                                            {{ $command->sent_at?->format('M j, g:i A') ?? '—' }}
                                        </td>
                                        <td class="d-none d-lg-table-cell text-muted small">
                                            @if ($command->acknowledged_at)
                                                {{ $command->acknowledged_at->format('M j, g:i A') }}
                                                @if ($command->return_code !== null)
                                                    <span class="text-muted">({{ $command->return_code }})</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No commands queued yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h3 class="h6 fw-semibold mb-0">Recent Attendance</h3>
                            <p class="text-muted small mb-0">Latest punches received from this device.</p>
                        </div>
                        @can('viewAny', App\Models\AttendanceLog::class)
                            <a href="{{ route('admin.attendance-logs.index', ['sn' => $device->serial_number]) }}" class="btn btn-sm btn-light">
                                View all
                            </a>
                        @endcan
                    </div>
                    <div class="table-responsive sg-data-table-wrapper">
                        <table class="table table-hover align-middle mb-0 sg-data-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">User ID</th>
                                    <th>Recorded At</th>
                                    <th class="d-none d-md-table-cell">Punch</th>
                                    <th class="d-none d-lg-table-cell">Verify</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentAttendance as $record)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $record->user_id }}</td>
                                        <td>{{ $record->timestamp->format('M j, Y g:i A') }}</td>
                                        <td class="d-none d-md-table-cell text-muted">{{ App\Enums\ZktecoPunchStatus::labelFor($record->punch_status) }}</td>
                                        <td class="d-none d-lg-table-cell text-muted">{{ App\Enums\ZktecoVerifyMode::labelFor($record->verify_mode) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No attendance records yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('manage', $device)
        <div class="modal fade" id="syncUserModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.zkteco-devices.users.store', $device) }}" method="POST">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Sync User to Device</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Queues a user upsert command. The device applies it on the next push poll.
                            </p>
                            <x-forms.input label="User PIN" name="user_id" placeholder="e.g. 1005" required />
                            <x-forms.input label="Name" name="name" placeholder="Member name" />
                            <x-forms.input label="UID" name="uid" type="number" min="0" />
                            <x-forms.input label="Card number" name="card_number" placeholder="RFID card number" />
                            <x-forms.input label="Privilege" name="privilege" type="number" min="0" max="14" />
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Queue Sync</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('admin.zkteco-devices.users.destroy', $device) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Delete User from Device</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Queues a delete command for the given user PIN on this device.
                            </p>
                            <x-forms.input label="User PIN" name="user_id" placeholder="e.g. 1005" required />
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Queue Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
