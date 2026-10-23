@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'block' => false,
    'pill' => false,
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'outline' => 'btn-outline-primary',
        'outline-secondary' => 'btn-outline-secondary',
        'danger' => 'btn-outline-danger',
        'danger-solid' => 'btn-danger',
        'success' => 'btn-success',
        'warning' => 'btn-warning',
        'ghost' => 'btn-ghost',
    ];

    $classes = [
        'btn',
        'ui-btn',
        $variants[$variant] ?? $variants['primary'],
        $size === 'sm' ? 'btn-sm' : null,
        $size === 'lg' ? 'btn-lg' : null,
        $block ? 'w-100' : null,
        $pill ? 'rounded-pill' : null,
    ];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </button>
@endif
