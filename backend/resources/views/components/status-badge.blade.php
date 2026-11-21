@props(['status'])
@php
    [$label, $tone] = match ($status) {
        'draft' => ['Draft', 'bg-ev-neutral-soft text-ev-neutral-ink'],
        'published' => ['Dipublikasikan', 'bg-ev-info-soft text-ev-info'],
        'completed' => ['Selesai', 'bg-ev-success-soft text-ev-success'],
        'cancelled' => ['Dibatalkan', 'bg-ev-neutral-soft text-ev-neutral-ink'],
        'todo' => ['Belum dimulai', 'bg-ev-neutral-soft text-ev-neutral-ink'],
        'in_progress' => ['Sedang dikerjakan', 'bg-ev-info-soft text-ev-info'],
        'done' => ['Selesai', 'bg-ev-success-soft text-ev-success'],
        default => [$status, 'bg-ev-neutral-soft text-ev-neutral-ink'],
    };
@endphp
<span {{ $attributes->class(['inline-flex rounded-full px-3 py-1 text-xs font-medium', $tone]) }}>{{ $label }}</span>
