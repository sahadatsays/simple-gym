@extends('layouts.admin', ['heading' => 'Renew Membership'])

@section('title', 'Renew Membership')

@section('content')
    <x-ui.page-header
        title="Renew Membership"
        subtitle="Search for a member to renew their membership"
    />

    <x-ui.card class="mb-4">
        <form action="{{ route('admin.members.renew.create') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="search" class="form-label">Search member</label>
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $search }}"
                    placeholder="Name, member ID, phone, or email..."
                    class="form-control"
                    autofocus
                >
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </x-ui.card>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Member</th>
                            <th>Phone</th>
                            <th>Current Plan</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
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
                                <td colspan="6" class="text-center py-5 text-muted">
                                    @if ($search !== '')
                                        No members found for "{{ $search }}".
                                    @else
                                        Search for a member to begin renewal.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
