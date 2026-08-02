@props([
    'variant' => 'secondary',
])

<span {{ $attributes->merge(['class' => "badge text-bg-{$variant}"]) }}>
    {{ $slot }}
</span>
