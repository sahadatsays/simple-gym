@props([
    'label',
    'for' => null,
])

<div {{ $attributes->merge(['class' => 'sg-filter-field']) }}>
    <label @if($for) for="{{ $for }}" @endif class="sg-filter-label">{{ $label }}</label>
    <div class="sg-filter-control">
        {{ $slot }}
    </div>
</div>
