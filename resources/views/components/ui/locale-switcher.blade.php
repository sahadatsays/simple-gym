@php
    $currentLocale = app()->getLocale();
    $locales = config('locale.labels', []);
@endphp

<div {{ $attributes->merge(['class' => 'sg-locale-switcher d-flex align-items-center gap-1']) }}>
    @foreach ($locales as $code => $label)
        @if (! $loop->first)
            <span class="text-muted small" aria-hidden="true">|</span>
        @endif

        @if ($code === $currentLocale)
            <span class="small fw-semibold text-body" aria-current="true">{{ $label }}</span>
        @else
            <a
                href="{{ route('locale.switch', $code) }}"
                class="small text-decoration-none text-muted"
            >
                {{ $label }}
            </a>
        @endif
    @endforeach
</div>
