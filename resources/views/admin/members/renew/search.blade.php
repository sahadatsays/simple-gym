@extends('layouts.admin', ['heading' => 'Renew Membership'])

@section('title', 'Renew Membership')

@section('content')
    <x-ui.page-header
        title="Renewal Review"
        subtitle="Expired members and memberships expiring within {{ $reminderDays }} days — renew before expiry"
    />

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.members.renew.create') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Name, member ID, phone, or email..."
                    class="form-control ps-2"
                    autofocus
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Sort by expiry" for="direction">
                <select name="direction" id="direction" class="form-select">
                    <option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>
                        Soonest first
                    </option>
                    <option value="desc" @selected(($filters['direction'] ?? 'asc') === 'desc')>
                        Latest first
                    </option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Per page" for="per_page">
                <select name="per_page" id="per_page" class="form-select">
                    @foreach ($perPageOptions as $option)
                        <option
                            value="{{ $option }}"
                            @selected((int) ($filters['per_page'] ?? config('gym.pagination.per_page')) === $option)
                        >
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.members.renew.create') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted small mb-0">
            {{ $members->total() }} {{ $members->total() === 1 ? 'member' : 'members' }} need renewal attention
        </p>
    </div>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Member</th>
                            <th>Phone</th>
                            <th>Current Plan</th>
                            <th>Expiry</th>
                            <th>Reminder</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            @php
                                $daysUntilExpiry = $member->daysUntilExpiry();
                                $isExpired = $daysUntilExpiry !== null && $daysUntilExpiry < 0;
                                $expiresToday = $daysUntilExpiry === 0;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $member->name }}</div>
                                    <div class="text-muted small">{{ $member->member_code }}</div>
                                </td>
                                <td class="text-muted">{{ $member->phone }}</td>
                                <td>{{ $member->membershipPlan?->name ?? '—' }}</td>
                                <td class="text-muted">
                                    {{ $member->membership_expires_at?->format('M j, Y') ?? '—' }}
                                </td>
                                <td>
                                    @if ($daysUntilExpiry === null)
                                        <span class="text-muted">—</span>
                                    @elseif ($isExpired)
                                        <span class="badge text-bg-danger">
                                            Expired {{ abs($daysUntilExpiry) }} day{{ abs($daysUntilExpiry) === 1 ? '' : 's' }} ago
                                        </span>
                                    @elseif ($expiresToday)
                                        <span class="badge text-bg-warning">Expires today</span>
                                    @else
                                        <span class="badge text-bg-warning">
                                            {{ $daysUntilExpiry }} day{{ $daysUntilExpiry === 1 ? '' : 's' }} left
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match (true) {
                                            $member->isActive() => 'sg-status-badge-active',
                                            $member->status === App\Enums\MemberStatus::Suspended => 'sg-status-badge-warning',
                                            default => 'sg-status-badge-inactive',
                                        };
                                    @endphp
                                    <span class="sg-status-badge {{ $badgeClass }}">
                                        {{ $member->isActive() ? 'Active' : $member->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    @can('renew', $member)
                                        <a
                                            href="{{ route('admin.members.renew.edit', $member) }}"
                                            class="btn btn-sm btn-primary"
                                        >
                                            Renew
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    @if (filled($filters['search'] ?? null))
                                        No members found for "{{ $filters['search'] }}" in the renewal review queue.
                                    @else
                                        No memberships need renewal within the next {{ $reminderDays }} days.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($members->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $members->links() }}
            </div>
        @endif
    </div>
@endsection
