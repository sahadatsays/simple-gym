@props([
    'member',
])

<div class="d-flex align-items-center gap-3">
    <x-admin.member-avatar :member="$member" />
    <div class="min-w-0">
        <div class="fw-semibold text-truncate">{{ $member->name }}</div>
        <div class="small text-muted text-truncate">{{ $member->member_code }}</div>
    </div>
</div>
