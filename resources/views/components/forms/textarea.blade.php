@props([
    'label',
    'name',
    'value' => null,
    'required' => false,
    'rows' => 4,
    'help' => null,
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        @class(['form-control', 'is-invalid' => $errors->has($name)])
        @if ($required) required @endif
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
