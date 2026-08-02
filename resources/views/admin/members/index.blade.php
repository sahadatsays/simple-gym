@extends('layouts.admin', ['heading' => 'Members'])

@section('title', 'Members')

@section('content')
    <x-ui.page-header title="Members" subtitle="Manage gym members and memberships">
        <x-slot:actions>
            @can('create', App\Models\Member::class)
                <a href="{{ route('admin.members.create') }}" class="btn btn-primary">Add Member</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.members.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Name, ID, phone, email, RFID..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (App\Enums\MemberStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Plan" for="membership_plan_id">
                <select name="membership_plan_id" id="membership_plan_id" class="form-select">
                    <option value="">All plans</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($filters['membership_plan_id'] ?? '') === (string) $plan->id)>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Gender" for="gender">
                <select name="gender" id="gender" class="form-select">
                    <option value="">All genders</option>
                    @foreach (App\Enums\Gender::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['gender'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.members.index') }}" class="btn btn-light">Reset</a>
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
                            <th>Member</th>
                            <th class="d-none d-md-table-cell">Phone</th>
                            <th class="d-none d-lg-table-cell">Plan</th>
                            <th class="d-none d-lg-table-cell">Expiry</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td class="ps-4">
                                    <x-admin.member-cell :member="$member" />
                                    <div class="small text-muted d-md-none mt-1">{{ $member->phone }}</div>
                                </td>
                                <td class="text-muted d-none d-md-table-cell">{{ $member->phone }}</td>
                                <td class="d-none d-lg-table-cell">
                                    {{ $member->membershipPlan?->name ?? '—' }}
                                </td>
                                <td class="d-none d-lg-table-cell text-muted">
                                    {{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($member->status) {
                                            App\Enums\MemberStatus::Active => 'sg-status-badge-active',
                                            App\Enums\MemberStatus::Suspended => 'sg-status-badge-warning',
                                            default => 'sg-status-badge-inactive',
                                        };
                                    @endphp
                                    <span class="sg-status-badge {{ $badgeClass }}">
                                        {{ $member->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.member-actions :member="$member" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No members found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($members->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $members->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
