@props(['event'])
<aside class="radar-featured" aria-label="Sorotan event mendatang">
@if($event)
    @php
        $clean = fn ($text) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $text ?? '');
        $start = $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id');
        $demo = Str::startsWith($event->title, '[SIMULASI]') || Str::startsWith($event->organization->name, '[SIMULASI]');
    @endphp
    <div class="featured-heading"><span>Segera di kampus</span></div>
    <div class="featured-event-core"><time class="featured-date" datetime="{{ $event->starts_at->toIso8601String() }}"><strong>{{ $start->format('d') }}</strong><span>{{ $start->isoFormat('MMM') }}</span><span>{{ $start->format('Y') }}</span></time><div class="min-w-0"><h2>@display($clean($event->title))</h2><p class="featured-time">{{ $start->format('H:i') }} WITA</p></div></div>
    <div class="featured-event-footer"><p>@display($clean($event->organization->name))</p><a class="featured-detail ev-link" href="{{ route('events.show', $event->id) }}">Lihat detail <x-icon name="arrow" size="17" /></a></div>
@else
    <div class="featured-heading"><span>Event mendatang</span><x-icon name="event" size="19" /></div>
    <h2 class="featured-empty-title">Belum ada sorotan mendatang.</h2><p class="featured-empty-copy">Belum ada event publik mendatang yang sesuai pilihan ini. Jelajahi hasil di bawah atau sesuaikan filter.</p>
@endif
</aside>
