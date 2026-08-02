@props([
    'label',
    'name',
    'value' => null,
    'placeholder' => '0.00',
    'required' => false,
    'help' => null,
    'symbol' => null,
])

@php
    $prefix = $symbol ?? ($currencySymbol ?? App\Support\CurrencyRegistry::symbol(config('gym.defaults.currency')));
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="input-group">
        <span class="input-group-text">{{ $prefix }}</span>
        <input
            type="number"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            step="0.01"
            min="0"
            @class(['form-control', 'is-invalid' => $errors->has($name)])
            @if ($required) required @endif
            {{ $attributes }}
        >
    </div>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
