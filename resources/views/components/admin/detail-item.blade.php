@props([
    'label',
    'value' => null,
])

<dt class="col-sm-5">{{ $label }}</dt>
<dd class="col-sm-7">
    @if ($value !== null)
        {{ $value }}
    @else
        {{ $slot }}
    @endif
</dd>
