@props([
    'placeholder' => 'Search...',
    'filters' => [],
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm sg-filter-bar']) }}>
    <div class="card-body p-4">
        {{ $slot }}
    </div>
</div>
