@props([
    'label',
    'name',
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => 'Select an option',
])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @class(['form-select', 'is-invalid' => $errors->has($name)])
        @if ($required) required @endif
        {{ $attributes }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected(old($name, $selected) == $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
