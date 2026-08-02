@props([
    'member',
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'lg' => 'sg-member-avatar-lg',
        'sm' => 'sg-member-avatar-sm',
        default => '',
    };
@endphp

@if ($member->photo_url)
    <img
        src="{{ $member->photo_url }}"
        alt="{{ $member->name }}"
        @class(['sg-member-avatar', $sizeClass])
    >
@else
    <div @class(['sg-member-avatar', 'sg-member-avatar-placeholder', $sizeClass]) aria-hidden="true">
        {{ $member->initials }}
    </div>
@endif
