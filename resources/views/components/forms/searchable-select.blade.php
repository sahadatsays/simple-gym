@props([
    'label',
    'name',
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => 'Select an option',
])

@php
    $fieldId = $attributes->get('id', $name);
    $optionItems = collect($options)
        ->map(fn (string $label, int|string $value): array => [
            'value' => (string) $value,
            'label' => $label,
        ])
        ->values()
        ->all();
    $selectedValue = old($name, $selected);
@endphp

<div
    class="mb-3 sg-searchable-select"
    x-data="searchableSelect({
        name: @js($name),
        options: @js($optionItems),
        selected: @js($selectedValue),
        placeholder: @js($placeholder),
        required: @js($required),
    })"
>
    <label for="{{ $fieldId }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="position-relative">
        <input type="hidden" :name="name" :value="selected">

        <div class="sg-searchable-select-control">
            <input
                type="text"
                id="{{ $fieldId }}"
                x-ref="input"
                x-model="search"
                class="form-control @error($name) is-invalid @enderror"
                :placeholder="placeholder"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="open"
                @focus="openMenu()"
                @click="openMenu()"
                @input="openMenu()"
                @keydown.escape.prevent="closeMenu()"
                @blur="handleBlur()"
            >

            <span class="sg-searchable-select-chevron" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708"/>
                </svg>
            </span>
        </div>

        <template x-teleport="body">
            <div
                x-show="open"
                x-transition
                :style="menuStyle"
                class="sg-searchable-select-menu"
                @mousedown.prevent
            >
                <template x-for="option in filteredOptions" :key="option.value">
                    <button
                        type="button"
                        class="sg-searchable-select-option"
                        :class="{ 'is-selected': selected === option.value }"
                        @mousedown.prevent="selectOption(option)"
                        @click.prevent="selectOption(option)"
                        x-text="option.label"
                    ></button>
                </template>

                <div x-show="filteredOptions.length === 0" class="sg-searchable-select-empty">
                    No results found
                </div>
            </div>
        </template>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
