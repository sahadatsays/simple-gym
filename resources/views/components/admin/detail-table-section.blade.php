@props(['title'])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm']) }}>
    <div class="card-body p-0">
        <div class="px-4 py-3 border-bottom">
            <h2 class="h6 fw-semibold mb-0">{{ $title }}</h2>
        </div>
        <div class="table-responsive sg-data-table-wrapper">
            {{ $slot }}
        </div>
    </div>
</div>
