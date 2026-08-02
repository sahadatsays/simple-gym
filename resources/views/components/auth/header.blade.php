@props([
    'title',
    'subtitle' => null,
])

<div class="text-center mb-4">
    <h1 class="h4 mb-1">{{ $title }}</h1>
    @if ($subtitle)
        <p class="text-muted mb-0">{{ $subtitle }}</p>
    @endif
</div>
