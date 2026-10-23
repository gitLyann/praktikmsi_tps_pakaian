@props([
    'label' => null,
    'name',
    'id' => null,
    'type' => 'text',
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        {{ $attributes->class(['form-control', 'ui-input', 'is-invalid' => filled($error)]) }}
    >

    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
