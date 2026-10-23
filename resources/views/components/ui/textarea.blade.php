@props([
    'label' => null,
    'name',
    'id' => null,
    'rows' => 3,
    'value' => null,
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

    <textarea
        name="{{ $name }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        {{ $attributes->class(['form-control', 'ui-textarea', 'is-invalid' => filled($error)]) }}
    >{{ $value ?? $slot }}</textarea>

    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
