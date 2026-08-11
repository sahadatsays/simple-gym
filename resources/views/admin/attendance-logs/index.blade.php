@extends('layouts.admin', ['heading' => 'Attendance Logs'])

@section('title', 'Attendance Logs')

@section('content')
    <x-ui.page-header title="Attendance Logs" subtitle="View check-in and check-out records synced from ZKTeco devices" />

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.attendance-logs.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="PIM or device serial..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Device" for="sn">
                <select name="sn" id="sn" class="form-select">
                    <option value="">All devices</option>
                    @foreach ($deviceOptions as $serialNumber => $label)
                        <option value="{{ $serialNumber }}" @selected(($filters['sn'] ?? '') === $serialNumber)>
                            {{ $serialNumber }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="From" for="from_date">
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.attendance-logs.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <p class="text-muted small mb-3">
        {{ number_format($logs->total()) }} {{ Str::plural('record', $logs->total()) }} found
    </p>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">PIM</th>
                            <th>Member</th>
                            <th>Device</th>
                            <th>Recorded At</th>
                            <th class="d-none d-md-table-cell">Punch</th>
                            <th class="d-none d-lg-table-cell">Verify</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $member = $members->get($log->pim);
                                $device = $devices->get($log->sn);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $log->pim }}</td>
                                <td>
                                    @if ($member)
                                        <a href="{{ route('admin.members.show', $member) }}" class="text-decoration-none fw-semibold">
                                            {{ $member->name }}
                                        </a>
                                        <div class="text-muted small">{{ $member->member_code }}</div>
                                    @else
                                        <span class="text-muted">Unknown member</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($device)
                                        <a href="{{ route('admin.zkteco-devices.show', $device) }}" class="text-decoration-none">
                                            {{ $log->sn }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $log->sn }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->timestamp->format('M j, Y g:i A') }}</td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge rounded-pill text-bg-light border">
                                        {{ App\Enums\ZktecoPunchStatus::labelFor($log->punch_status) }}
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge rounded-pill text-bg-light border">
                                        {{ App\Enums\ZktecoVerifyMode::labelFor($log->verify_mode) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No attendance records found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters, or wait for devices to sync data.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
