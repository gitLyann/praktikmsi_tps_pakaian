@props([
    'label' => null,
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
])

@php
    $fieldId = $id ?? $name;
@endphp

<div class="ui-field">
    @if($label)
        <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        {{ $attributes->class(['form-select', 'ui-select', 'is-invalid' => filled($error)]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach

        {{ $slot }}
    </select>

    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
