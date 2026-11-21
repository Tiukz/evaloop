@props(['lesson', 'fullTime' => false, 'editorial' => false])
@php($approved = $lesson->approved_at?->copy()->timezone('Asia/Makassar')->locale('id'))
<p {{ $attributes->class(['leading-5 wrap-anywhere text-ev-neutral-ink', 'text-sm' => $editorial, 'text-xs' => !$editorial]) }}>
    @display($lesson->author->name) ·
    @if($approved)
        Disetujui <time datetime="{{ $approved->toIso8601String() }}">{{ $approved->isoFormat($fullTime ? 'D MMM YYYY · HH:mm' : 'D MMM YYYY') }}{{ $fullTime ? ' WITA' : '' }}</time>
    @else
        Internal, belum disetujui
    @endif
</p>
