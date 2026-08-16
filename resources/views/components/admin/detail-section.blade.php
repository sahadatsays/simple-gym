@props(['title'])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm h-100']) }}>
    <div class="card-body">
        <h2 class="h6 fw-semibold mb-3">{{ $title }}</h2>
        {{ $slot }}
    </div>
</div>
