@props([
    'type' => 'primary',
    'size' => null,
])

@php
    $classes = collect([
        'btn',
        "btn-{$type}",
        $size ? "btn-{$size}" : null,
    ])->filter()->implode(' ');
@endphp

<button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
