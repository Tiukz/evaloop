@props(['name', 'size' => 20])
@php
    // Stable application aliases mapped to official Lucide assets, kept locally.
    $icon = match ($name) {
        'radar' => 'radar',
        'home' => 'house',
        'organization' => 'building',
        'event' => 'calendar-days',
        'task' => 'clipboard-check',
        'arrow' => 'arrow-right',
        'people' => 'users',
        'book' => 'book-open',
        'file' => 'file',
        'image' => 'image',
        'sparkles' => 'sparkles',
        'location' => 'map-pin',
        'search' => 'search',
        'chevron' => 'chevron-down',
        'menu' => 'menu',
        'sidebar' => 'panel-left',
        'close' => 'x',
        default => 'minus',
    };
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes->class(['shrink-0']) }}>
    <use href="{{ asset('icons/lucide.svg') }}#{{ $icon }}" />
</svg>
