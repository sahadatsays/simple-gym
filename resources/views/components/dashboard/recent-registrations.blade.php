@props([
    'members',
])

<x-dashboard.widget :title="__('dashboard.widgets.recent_registrations')" :subtitle="__('dashboard.widgets.recent_registrations_subtitle')">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sg-dashboard-table">
            <thead>
                <tr>
                    <th>{{ __('common.table.member') }}</th>
                    <th class="d-none d-sm-table-cell">{{ __('common.table.code') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th class="d-none d-md-table-cell">{{ __('common.table.joined') }}</th>
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
                                {{ __('enums.member_status.'.$member->status->value) }}
                            </x-ui.badge>
                        </td>
                        <td class="d-none d-md-table-cell text-muted text-nowrap">
                            {{ $member->joined_at->format('M j, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            {{ __('dashboard.widgets.no_registrations') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($members->isNotEmpty())
        <div class="text-end mt-3">
            <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-light">{{ __('dashboard.widgets.view_all_members') }}</a>
        </div>
    @endif
</x-dashboard.widget>
