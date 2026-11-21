@props(['done', 'total', 'inProgress' => 0, 'label' => 'Progres tugas'])
@php
    $tone = match (true) {
        $total > 0 && $done === $total => 'bg-ev-success',
        $done > 0 || $inProgress > 0 => 'bg-ev-info',
        default => 'bg-ev-neutral',
    };
@endphp
@if($total > 0)
    <div {{ $attributes->class(['h-1.5 overflow-hidden rounded-full bg-ev-neutral/20']) }}
         role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}"
         aria-valuetext="{{ $done }} dari {{ $total }} tugas selesai">
        <span @class(['block h-full rounded-full', $tone]) style="width: {{ round($done / $total * 100, 4) }}%"></span>
    </div>
@endif
