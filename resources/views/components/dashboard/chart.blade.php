@props([
    'id',
    'type' => 'line',
    'labels' => [],
    'values' => [],
    'label' => 'Value',
    'color' => '#2563eb',
    'currency' => null,
])

<div {{ $attributes->merge(['class' => 'sg-dashboard-chart']) }}>
    <canvas
        id="{{ $id }}"
        data-chart-type="{{ $type }}"
        data-chart-labels='@json($labels)'
        data-chart-values='@json($values)'
        data-chart-label="{{ $label }}"
        data-chart-color="{{ $color }}"
        @if ($currency) data-chart-currency="{{ $currency }}" @endif
    ></canvas>
</div>
