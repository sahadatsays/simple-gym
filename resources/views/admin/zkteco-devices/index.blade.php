@extends('layouts.admin', ['heading' => 'ZKTeco Devices'])

@section('title', 'ZKTeco Devices')

@section('content')
    <x-ui.page-header
        title="ZKTeco Devices"
        subtitle="Approve devices, monitor connectivity, and queue sync commands"
    />

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.zkteco-devices.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Serial number or protocol..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.zkteco-devices.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted small mb-0">
            {{ $devices->total() }} device{{ $devices->total() === 1 ? '' : 's' }} registered
        </p>
    </div>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Serial Number</th>
                            <th class="d-none d-md-table-cell">Protocol</th>
                            <th class="d-none d-lg-table-cell">Last Seen</th>
                            <th class="d-none d-xl-table-cell">Commands</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($devices as $device)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.zkteco-devices.show', $device) }}" class="fw-semibold text-decoration-none">
                                        {{ $device->serial_number }}
                                    </a>
                                    <div class="small text-muted d-md-none">
                                        {{ $device->protocol_generation }}
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell text-muted">
                                    {{ $device->protocol_generation }}
                                </td>
                                <td class="d-none d-lg-table-cell text-muted">
                                    {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td class="d-none d-xl-table-cell text-muted">
                                    {{ $device->commands_count }}
                                </td>
                                <td>
                                    <span class="sg-status-badge {{ $device->status->badgeClass() }}">
                                        {{ $device->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.zkteco-device-actions :device="$device" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No devices found</h3>
                                        <p class="text-muted small mb-0">
                                            Devices appear here after they connect to the ADMS endpoints.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($devices->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $devices->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
