@props([
    'scroll' => true,
    'label' => 'Tabel data',
])

<div
    role="region"
    aria-label="{{ $label }}"
    tabindex="0"
    {{ $attributes->class(['table-responsive', 'ui-table-wrap', 'ui-table-scroll' => $scroll]) }}
>
    {{ $slot }}
</div>
