@props([
    'icon' => null,
])

<span {{ $attributes->class(['lm-chip', 'ui-chip']) }}>
    @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
