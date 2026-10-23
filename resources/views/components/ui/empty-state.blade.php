@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'description' => null,
])

<div {{ $attributes->class(['lm-empty', 'ui-empty']) }}>
    @if($icon)
        <span class="ui-empty-icon" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>
    @endif

    @if($title)
        <div class="lm-empty-title ui-empty-title">{{ $title }}</div>
    @endif

    @if($description)
        <div class="lm-empty-text ui-empty-text">{{ $description }}</div>
    @endif

    @if(! $slot->isEmpty())
        <div class="ui-empty-slot">{{ $slot }}</div>
    @endif
</div>
