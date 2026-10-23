@props([
    'name' => null,
    'src' => null,
    'size' => 'md',
])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class(['ui-avatar', 'ui-avatar-' . $size]) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name ?? 'Avatar' }}" loading="lazy">
    @else
        <span aria-hidden="true">{{ $initials !== '' ? $initials : '?' }}</span>
        @if($name)
            <span class="visually-hidden">{{ $name }}</span>
        @endif
    @endif
</span>
