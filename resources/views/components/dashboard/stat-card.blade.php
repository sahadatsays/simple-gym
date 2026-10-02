@props([
    'title',
    'value',
    'icon' => 'users',
    'variant' => 'primary',
    'formatted' => false,
])

@php
    $variants = ['primary', 'success', 'danger', 'warning', 'info', 'purple', 'dark'];
    $variantClass = in_array($variant, $variants, true) ? $variant : 'primary';
@endphp

<div {{ $attributes->merge(['class' => 'card sg-dashboard-stat sg-dashboard-stat--'.$variantClass.' h-100 border-0']) }}>
    <div class="card-body d-flex align-items-start justify-content-between gap-3">
        <div class="min-w-0">
            <p class="sg-dashboard-stat-label">{{ $title }}</p>
            <h3 class="sg-dashboard-stat-value">
                @if ($formatted)
                    {{ $value }}
                @else
                    {{ is_numeric($value) ? number_format((float) $value) : $value }}
                @endif
            </h3>
            @if (isset($footer))
                <div class="sg-dashboard-stat-footer">{{ $footer }}</div>
            @endif
        </div>

        <div class="sg-dashboard-stat-icon">
            @include('components.dashboard.icons.'.$icon)
        </div>
    </div>
</div>
