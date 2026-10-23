@props([
    'items' => [],
    'active' => null,
    'dss' => false,
    'label' => 'Navigasi tab',
])

<ul
    role="tablist"
    aria-label="{{ $label }}"
    {{ $attributes->class(['nav', 'nav-pills', 'ui-tabs', 'flex-wrap', 'gap-1']) }}
    @if($dss) data-dss-tabs @endif
>
    @foreach($items as $item)
        @php
            $isActive = (bool) ($item['active'] ?? ($active !== null && $active === ($item['id'] ?? null)));
        @endphp
        <li class="nav-item" role="presentation">
            <a
                href="#{{ $item['id'] ?? '' }}"
                role="tab"
                class="nav-link ui-tab {{ $isActive ? 'active' : '' }}"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                @if($dss) data-dss-tab="{{ $item['id'] ?? '' }}" @endif
            >
                @if(! empty($item['icon']))
                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                @endif
                {{ $item['label'] ?? '' }}
            </a>
        </li>
    @endforeach
</ul>
