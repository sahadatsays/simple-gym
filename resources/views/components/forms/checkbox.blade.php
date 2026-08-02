@props([
    'label',
    'name',
    'checked' => false,
    'value' => '1',
])

<div class="form-check mb-3">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        @class(['form-check-input', 'is-invalid' => $errors->has($name)])
        @checked(old($name, $checked))
        {{ $attributes }}
    >
    <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
