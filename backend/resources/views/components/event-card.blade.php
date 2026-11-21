@props(['event', 'referenceTime' => null])
@php
    $start = $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id');
    $end = $event->ends_at->copy()->timezone('Asia/Makassar');
    $clock = $referenceTime ?? now();
    $historical = $event->status !== 'published' || $event->ends_at->lte($clock);
    $clean = fn ($text) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $text ?? '');
    $scope = $event->organization->studyProgram?->name ?? $event->organization->faculty?->name ?? 'Tingkat universitas';
    $state = match (true) {
        $event->status === 'cancelled' => 'Dibatalkan',
        $event->status === 'completed' => 'Selesai',
        $event->ends_at->lte($clock) => 'Jadwal berlalu',
        $event->starts_at->lte($clock) => 'Sedang berlangsung',
        default => 'Mendatang',
    };
    $title = $clean($event->title);
@endphp
<article @class(['radar-event', 'radar-event-history' => $historical])>
    <x-event-poster :event="$event" />
    <div class="radar-event-body">
        <div class="radar-event-flags"><time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $start->isoFormat('D MMM YYYY') }}</time><span @class(['radar-state', 'radar-state-active' => !$historical])>{{ $state }}</span></div>
        <h3 class="radar-event-title"><a class="radar-card-link ev-link" href="{{ route('events.show', $event->id) }}">{{ $title }}</a></h3>
        <p class="radar-organizer" title="@display($clean($event->organization->name))">@display($clean($event->organization->name))</p>
        <p class="sr-only">{{ $clean($scope) }}</p>
        @if($event->description)<p class="radar-event-description">@display(Str::limit($clean($event->description), 220))</p>@endif
        <div class="radar-event-facts"><p><x-icon name="event" size="14" /><span>{{ $start->format('H:i') }} – {{ $end->isSameDay($start) ? $end->format('H:i') : $end->format('d M Y H:i') }} WITA</span></p><p><x-icon name="location" size="14" /><span>@display($clean($event->venue) ?: 'Lokasi belum ditentukan')</span></p></div>
        @if($event->status === 'cancelled')<p class="mt-2 text-xs text-slate-600">Dibatalkan · bukan kegiatan aktif</p>@endif
    </div>
    <footer class="radar-event-footer"><span>Lihat detail</span><x-icon name="arrow" size="18" /></footer>
</article>
