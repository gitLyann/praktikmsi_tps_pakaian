@props([
    'title' => null,
    'icon' => null,
    'hoverable' => false,
    'flush' => false,
])

<div {{ $attributes->class(['card', 'ui-card', 'ui-card-hover' => $hoverable]) }}>
    @if($title || isset($header) || isset($actions))
        <div class="card-header ui-card-header">
            <div class="ui-card-heading">
                @if($icon)
                    <span class="ui-card-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                @endif
                @if($title)
                    <h5 class="ui-card-title">{{ $title }}</h5>
                @endif
                {{ $header ?? '' }}
            </div>
            @isset($actions)
                <div class="ui-card-actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="card-body {{ $flush ? 'p-0' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer ui-card-footer">{{ $footer }}</div>
    @endisset
</div>
