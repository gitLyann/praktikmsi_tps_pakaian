@props([
    'label',
    'icon' => null,
    'variant' => 'outline-secondary',
    'align' => 'start',
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'outline' => 'btn-outline-primary',
        'outline-secondary' => 'btn-outline-secondary',
        'ghost' => 'btn-ghost',
    ];
@endphp

<div class="dropdown ui-dropdown">
    <button
        type="button"
        class="btn btn-sm {{ $variants[$variant] ?? $variants['outline-secondary'] }} ui-btn"
        data-bs-toggle="dropdown"
        aria-expanded="false"
    >
        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $label }}
    </button>

    <ul class="dropdown-menu ui-dropdown-menu {{ $align === 'end' ? 'dropdown-menu-end' : '' }}">
        {{ $slot }}
    </ul>
</div>
