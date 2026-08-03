@props([
    'members',
])

<x-dashboard.widget title="Recent Registrations" subtitle="New members in the selected period">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th class="d-none d-sm-table-cell">Code</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>
                            <a href="{{ route('admin.members.show', $member) }}" class="fw-semibold text-decoration-none">
                                {{ $member->name }}
                            </a>
                        </td>
                        <td class="d-none d-sm-table-cell text-muted">{{ $member->member_code }}</td>
                        <td>
                            <x-ui.badge :variant="$member->status->badgeVariant()">
                                {{ $member->status->label() }}
                            </x-ui.badge>
                        </td>
                        <td class="d-none d-md-table-cell text-muted text-nowrap">
                            {{ $member->joined_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No registrations found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($members->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-light">View all members</a>
        </div>
    @endif
</x-dashboard.widget>
