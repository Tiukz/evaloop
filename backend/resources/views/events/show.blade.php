@extends('layouts.app')
@section('title', $event->title)
@section('breadcrumb', 'Event Radar / Detail kegiatan')
@section('content')
@php
    $clean = fn ($text) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $text ?? '');
    $start = $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id');
    $end = $event->ends_at->copy()->timezone('Asia/Makassar')->locale('id');
    $organization = $event->organization;
    $scope = $organization->studyProgram?->name ?? $organization->faculty?->name ?? 'Tingkat universitas';
    $level = $organization->study_program_id ? 'Program studi' : ($organization->faculty_id ? 'Fakultas' : 'Universitas');
    $historical = $event->status !== 'published' || $event->ends_at->lte(now());
@endphp
<div class="event-detail-page">
    <nav aria-label="Navigasi kembali" class="event-detail-back"><a class="ev-link" href="{{ route('events.index') }}"><x-icon name="arrow" size="16" class="rotate-180" />Kembali ke Event Radar</a></nav>
    <article aria-labelledby="event-detail-title">
        <div class="event-detail-hero">
            <figure @class(['event-detail-visual', 'event-detail-history' => $historical]) aria-label="Poster @display($clean($event->title))">
                <x-event-poster :event="$event" />
                
            </figure>
            <div class="event-detail-information">
                <div class="event-detail-status"><x-status-badge :status="$event->status" /> @if($event->status === 'published' && $historical)<span class="text-xs text-slate-600">Jadwal berlalu</span>@endif</div>
                <header><h1 id="event-detail-title">@display($clean($event->title))</h1><p class="event-detail-organizer-name">@display($clean($organization->name))</p><p class="event-detail-scope">{{ $clean($scope) }}</p></header>
                <div class="event-detail-facts">
                    <div><span class="event-detail-fact-icon" aria-hidden="true"><x-icon name="event" size="20" /></span><dl><dt>Jadwal kegiatan</dt><dd><time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $start->isoFormat('dddd, D MMMM YYYY') }}</time>@if(!$end->isSameDay($start))<span> – </span><time datetime="{{ $event->ends_at->toIso8601String() }}">{{ $end->isoFormat('dddd, D MMMM YYYY') }}</time>@endif<span class="event-detail-time">{{ $start->format('H:i') }} – {{ $end->format('H:i') }} WITA</span></dd></dl></div>
                    <div><span class="event-detail-fact-icon" aria-hidden="true"><x-icon name="location" size="20" /></span><dl><dt>Lokasi</dt><dd>@display($clean($event->venue) ?: 'Lokasi belum ditentukan')</dd></dl></div>
                </div>
                @if($event->status === 'cancelled')<p class="event-detail-cancelled">Kegiatan dibatalkan. Informasi ini ditampilkan sebagai riwayat publik.</p>@endif
                @can('viewWorkspace', $event)<div class="event-detail-workspace"><x-button variant="secondary" :href="route('workspace.overview', $event)">Buka workspace event</x-button></div>@endcan
            </div>
        </div>
        <div class="event-detail-reading">
            <section class="event-detail-about" aria-labelledby="event-about-heading"><h2 id="event-about-heading">Tentang kegiatan</h2><p>@display($event->description ?: 'Deskripsi belum tersedia.')</p></section>
            <aside class="event-detail-organizer" aria-labelledby="event-organizer-heading"><p class="event-detail-section-label">Penyelenggara</p><h2 id="event-organizer-heading">Diselenggarakan oleh</h2><div class="flex items-center gap-3">
                @if($organization->logo_path)
                    <img src="{{ route('organizations.logo.show', $organization) }}" alt="" width="44" height="44" class="size-11 shrink-0 rounded-lg border border-ev-border bg-white object-contain p-1">
                @endif
                <p class="event-detail-organization">@display($clean($organization->name))</p>
            </div><dl><div><dt>Lingkup organisasi</dt><dd>{{ $level }}</dd></div>@if($organization->faculty)<div><dt>Fakultas</dt><dd>@display($clean($organization->faculty->name))</dd></div>@endif @if($organization->studyProgram)<div><dt>Program studi</dt><dd>@display($clean($organization->studyProgram->name))</dd></div>@endif</dl></aside>
        </div>
    </article>
</div>
@endsection
