@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4']) }}>
    <div>
        @if ($title)
            <h1 class="h3 mb-1">{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="d-flex flex-wrap gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
