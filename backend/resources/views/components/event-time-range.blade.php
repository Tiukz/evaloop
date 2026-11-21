@props(['event'])

@php
    $start = $event->starts_at?->copy()->timezone('Asia/Makassar')->locale('id');
    $end = $event->ends_at?->copy()->timezone('Asia/Makassar')->locale('id');
@endphp

@if($start || $end)
    @if($start)
        <time datetime="{{ $start->toIso8601String() }}">{{ $start->isoFormat('D MMM YYYY · HH:mm') }}</time>
    @else
        <span>Awal belum ditentukan</span>
    @endif
    –
    @if($end)
        <time datetime="{{ $end->toIso8601String() }}">{{ $start?->isSameDay($end) ? $end->format('H:i') : $end->isoFormat('D MMM YYYY · HH:mm') }}</time>
    @else
        <span>Akhir belum ditentukan</span>
    @endif
    WITA
@else
    <span>Belum ditentukan</span>
@endif
