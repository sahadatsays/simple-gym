@extends('layouts.admin', ['heading' => 'Lockers'])

@section('title', 'Lockers')

@section('content')
    <x-ui.page-header title="Lockers" subtitle="Manage locker numbers, locations, and monthly fees">
        <x-slot:actions>
            @can('create', App\Models\Locker::class)
                <a href="{{ route('admin.lockers.create') }}" class="btn btn-primary">
                    Add Locker
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.lockers.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Number, location, type, or notes..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\LockerStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.lockers.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Locker Number</th>
                            <th class="d-none d-md-table-cell">Location</th>
                            <th class="d-none d-lg-table-cell">Type</th>
                            <th>Monthly Fee</th>
                            <th>Status</th>
                            <th class="d-none d-xl-table-cell">Created By</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lockers as $locker)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.lockers.show', $locker) }}" class="fw-semibold text-decoration-none">
                                        {{ $locker->locker_number }}
                                    </a>
                                    @if ($locker->notes)
                                        <div class="small text-muted text-truncate d-none d-md-block" style="max-width: 220px;">
                                            {{ $locker->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">{{ $locker->location ?: '—' }}</td>
                                <td class="d-none d-lg-table-cell">{{ $locker->category ?: '—' }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($locker->monthly_fee, $gymCurrency) }}</td>
                                <td>
                                    <span class="sg-status-badge {{ $locker->status->badgeClass() }}">{{ $locker->status->label() }}</span>
                                </td>
                                <td class="d-none d-xl-table-cell">{{ $locker->creator?->name ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <x-admin.locker-actions :locker="$locker" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No lockers found</h3>
                                        <p class="text-muted small mb-0">Add a locker or adjust your search.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($lockers->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $lockers->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
