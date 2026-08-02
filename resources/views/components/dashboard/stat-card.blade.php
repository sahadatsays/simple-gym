@props([
    'title',
    'value',
    'icon' => 'users',
    'variant' => 'primary',
    'formatted' => false,
])

@php
    $variants = [
        'primary' => ['bg' => 'bg-primary-subtle', 'text' => 'text-primary', 'accent' => '#2563eb'],
        'success' => ['bg' => 'bg-success-subtle', 'text' => 'text-success', 'accent' => '#16a34a'],
        'danger' => ['bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'accent' => '#dc2626'],
        'warning' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning-emphasis', 'accent' => '#d97706'],
        'info' => ['bg' => 'bg-info-subtle', 'text' => 'text-info-emphasis', 'accent' => '#0891b2'],
        'purple' => ['bg' => 'bg-light', 'text' => 'text-dark', 'accent' => '#7c3aed'],
        'dark' => ['bg' => 'bg-dark-subtle', 'text' => 'text-dark', 'accent' => '#334155'],
    ];

    $palette = $variants[$variant] ?? $variants['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'card sg-dashboard-stat h-100 border-0 shadow-sm']) }}>
    <div class="card-body d-flex align-items-start justify-content-between gap-3">
        <div class="min-w-0">
            <p class="text-muted small fw-semibold text-uppercase mb-2">{{ $title }}</p>
            <h3 class="h4 mb-0 fw-bold text-truncate">
                @if ($formatted)
                    {{ $value }}
                @else
                    {{ is_numeric($value) ? number_format((float) $value) : $value }}
                @endif
            </h3>
            @if (isset($footer))
                <div class="mt-2 small text-muted">{{ $footer }}</div>
            @endif
        </div>

        <div class="sg-dashboard-stat-icon {{ $palette['bg'] }} {{ $palette['text'] }}">
            @include('components.dashboard.icons.'.$icon)
        </div>
    </div>
</div>
