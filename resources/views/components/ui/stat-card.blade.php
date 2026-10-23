@props([
    'label',
    'value',
    'note' => null,
    'icon' => null,
    'tone' => null,
])

<div {{ $attributes->class(['card', 'ui-card', 'ui-stat-card', 'ui-card-hover']) }}>
    <div class="ui-stat-body">
        <div class="ui-stat-head">
            @if($icon)
                <span class="lm-stat-icon {{ $tone ? 'is-' . $tone : '' }}" aria-hidden="true">
                    <i class="{{ $icon }}"></i>
                </span>
            @endif
            <span class="ui-stat-label">{{ $label }}</span>
        </div>
        <div class="ui-stat-value">{{ $value }}</div>
        @if($note)
            <div class="ui-stat-note">{{ $note }}</div>
        @endif
    </div>
</div>
