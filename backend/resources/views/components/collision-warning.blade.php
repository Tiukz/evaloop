@props(['event', 'types'])

@php
    $labels = ['time_overlap' => 'Waktu', 'venue_overlap' => 'Lokasi', 'audience_overlap' => 'Target'];
@endphp

<li class="grid min-w-0 items-center gap-x-6 gap-y-1.5 px-4 py-3 @min-[40rem]:grid-cols-[minmax(0,1fr)_auto] @min-[40rem]:px-5">
    <div class="min-w-0">
        <h3 id="collision-event-{{ $event->id }}" class="type-card wrap-anywhere">@display($event->title)</h3>
        <p class="mt-1 text-sm leading-6 text-ev-info"><x-event-time-range :event="$event" /></p>
    </div>
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-x-5 gap-y-1 @min-[40rem]:justify-end">
        <ul role="list" class="flex flex-wrap gap-1.5" aria-label="Jenis irisan jadwal">
            @foreach($types as $type)
                <li @class([
                    'rounded-full px-2.5 py-1 text-xs font-medium',
                    'bg-ev-info-soft text-ev-info' => $type === 'time_overlap',
                    'bg-ev-warning-soft text-ev-warning' => $type !== 'time_overlap',
                ])>{{ $labels[$type] }}</li>
            @endforeach
        </ul>
        <a href="{{ route('events.show', $event->id) }}" class="ev-link inline-flex min-h-11 items-center gap-1.5 rounded-sm text-sm font-medium text-ev-primary hover:underline"
           aria-labelledby="collision-detail-{{ $event->id }} collision-event-{{ $event->id }}">
            <span id="collision-detail-{{ $event->id }}">Lihat detail</span>
            <x-icon name="arrow" size="14" />
        </a>
    </div>
</li>
