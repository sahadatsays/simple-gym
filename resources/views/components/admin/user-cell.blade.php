@props([
    'user',
])

@php
    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="d-flex align-items-center gap-3">
    <div class="sg-user-avatar" aria-hidden="true">{{ $initials }}</div>
    <div class="min-w-0">
        <div class="fw-semibold text-truncate">{{ $user->name }}</div>
        <div class="small text-muted text-truncate">{{ $user->username }}</div>
    </div>
</div>
