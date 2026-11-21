@props(['href' => null, 'type' => 'submit', 'variant' => 'primary', 'size' => 'md', 'loadingLabel' => 'Memproses…'])
@php
    $variantClass = match ($variant) {
        'secondary' => 'ev-button-secondary',
        'ghost' => 'ev-button-ghost',
        'danger' => 'ev-button-danger',
        default => 'ev-button-primary',
    };
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['ev-button', $variantClass, 'ev-button-sm' => $size === 'sm']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['ev-button', $variantClass, 'ev-button-sm' => $size === 'sm']) }}>
        <span class="ev-button-label">{{ $slot }}</span>
        <span class="ev-button-pending" aria-hidden="true"><span class="ev-spinner"></span>{{ $loadingLabel }}</span>
    </button>
@endif
