@props([
    'label',
    'name',
    'value' => null,
    'placeholder' => 'Select date',
    'required' => false,
    'help' => null,
    'maxDate' => null,
    'minDate' => null,
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <input
        type="text"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        data-picker="date"
        @if ($maxDate) data-max-date="{{ $maxDate }}" @endif
        @if ($minDate) data-min-date="{{ $minDate }}" @endif
        @class(['form-control', 'sg-date-picker', 'is-invalid' => $errors->has($name)])
        @if ($required) required @endif
        autocomplete="off"
        {{ $attributes }}
    >

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
