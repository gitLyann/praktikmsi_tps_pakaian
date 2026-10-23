@props([
    'type' => 'info',
    'dismissible' => true,
    'icon' => null,
])

@php
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info' => 'bi-info-circle-fill',
    ];

    $iconClass = $icon ?? ($icons[$type] ?? $icons['info']);
@endphp

<div
    role="alert"
    {{ $attributes->class([
        'alert',
        'ui-toast',
        'alert-' . $type,
        'alert-dismissible' => $dismissible,
        'fade',
        'show',
    ]) }}
>
    <span class="ui-toast-icon" aria-hidden="true">
        <i class="bi {{ $iconClass }}"></i>
    </span>

    <div class="ui-toast-body">
        {{ $slot }}
    </div>

    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    @endif
</div>
