@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        @class(['form-control', 'is-invalid' => $errors->has($name)])
        @if ($required) required @endif
        {{ $attributes }}
    >

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
