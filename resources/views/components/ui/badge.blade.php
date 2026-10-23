@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-primary text-white',
        'success' => 'bg-success',
        'warning' => 'bg-warning text-dark',
        'danger' => 'bg-danger',
        'info' => 'bg-info text-dark',
        'neutral' => 'bg-secondary bg-opacity-10 text-secondary',
        'dark' => 'bg-dark text-white',
        'light' => 'bg-light text-dark',
    ];
@endphp

<span {{ $attributes->class(['badge', 'ui-badge', $variants[$variant] ?? $variants['neutral']]) }}>
    @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
