@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'card sg-dashboard-widget border-0 shadow-sm h-100']) }}>
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
                <h2 class="h6 mb-0 fw-bold">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-muted small mb-0 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="d-flex gap-2">{{ $actions }}</div>
            @endif
        </div>
    </div>

    <div class="card-body px-4 pb-4 pt-3">
        {{ $slot }}
    </div>
</div>
