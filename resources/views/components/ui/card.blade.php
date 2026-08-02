@props([
    'title' => null,
    'padding' => 'p-4',
])

<div {{ $attributes->merge(['class' => "card border-0 shadow-sm {$padding}"]) }}>
    @if ($title)
        <div class="card-header bg-transparent border-0 px-0 pt-0 pb-3">
            <h2 class="h5 mb-0">{{ $title }}</h2>
        </div>
    @endif

    {{ $slot }}
</div>
