@extends('layouts.app')
@section('title', $event->title)
@section('breadcrumb', 'Event Workspace / Ringkasan')
@section('content')
@include('events.partials.command-center-styles')
@include('events.partials.workspace-module-styles')
@php
    $completed = $event->status === 'completed';
    $start = $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id');
    $end = $event->ends_at->copy()->timezone('Asia/Makassar')->locale('id');
    $total = $taskCounts->sum();
    $done = $taskCounts['done'] ?? 0;
    $meetings = $archived && $recentMeeting ? [$recentMeeting] : $upcomingMeetings;
    $taskLabels = ['todo' => 'Belum dimulai', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'];
@endphp
<div class="event-command-center" data-event-state="{{ $event->status }}">
    <a class="ecc-back ev-link" href="{{ route('my.events') }}"><x-icon name="arrow" size="14" class="rotate-180" />Event Saya</a>
    <header class="ecc-masthead">
        <div class="ecc-poster"><x-event-poster :event="$event" /></div>
        <div class="ecc-identity">
            <h1>@display($event->title)</h1>
            <p class="ecc-organizer">@display($event->organization->name)</p>
            <div class="ecc-metadata">
                <x-status-badge :status="$event->status" />
                <span class="text-ev-info"><x-icon name="event" size="15" /><time datetime="{{ $start->toIso8601String() }}">{{ $start->isoFormat('D MMM YYYY') }}</time></span>
                @if($event->venue)
                    <span><x-icon name="location" size="15" />@display($event->venue)</span>
                @elseif(!$archived)
                    <span><x-icon name="location" size="15" />Lokasi belum ditentukan</span>
                @endif
            </div>
        </div>
        @can('manage', $event)
            <div class="ecc-header-actions">
                <x-button variant="secondary" size="sm" :href="route('workspace.edit', $event)">Edit event</x-button>
                @if($event->status === 'draft')
                    @can('publish', $event)
                        <x-button size="sm" :href="route('workspace.publish.confirm', $event)">Publikasikan event</x-button>
                    @endcan
                @elseif($event->canBeCompleted())
                    @can('complete', $event)
                        <x-button size="sm" :href="route('workspace.complete.confirm', $event)">Tandai selesai</x-button>
                    @endcan
                @endif
            </div>
        @endcan
    </header>

    <x-workspace-tabs :event="$event" active="overview" />

    <div class="ecc-body">
        <div class="ecc-primary">
            @if($completed)
                <section aria-labelledby="ecc-legacy-heading">
                    <div class="ecc-section-head">
                        <h2 id="ecc-legacy-heading">Pembelajaran kegiatan</h2>
                        <a class="ecc-link ev-link" href="{{ route('workspace.legacy', $event) }}">Lihat semua evaluasi</a>
                    </div>
                    @forelse($approvedLessons as $lesson)
                        <article class="ecc-lesson">
                            <h3 class="line-clamp-2">@display($lesson->problem)</h3>
                            <p class="ecc-recommendation line-clamp-3">@display($lesson->recommendation)</p>
                        </article>
                    @empty
                        <p class="ecc-quiet">Belum ada evaluasi yang disetujui.</p>
                        @can('manage', $event)
                            <a class="ecc-link ev-link" href="{{ route('evaluations.create', $event) }}">Catat evaluasi kegiatan</a>
                        @endcan
                    @endforelse
                </section>
            @endif

            @if(!$archived)
                @include('events.partials.overview-tasks')
            @endif

            <section aria-labelledby="ecc-meetings-heading">
                <div class="ecc-section-head">
                    <h2 id="ecc-meetings-heading">{{ $archived ? 'Keputusan & riwayat rapat' : 'Rapat terdekat' }}</h2>
                    <a class="ecc-link ev-link" href="{{ route('workspace.meetings', $event) }}">Buka Meeting Hub</a>
                </div>
                @forelse($meetings as $meeting)
                    @php($scheduled = $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->locale('id'))
                    <a class="ecc-meeting-row ev-link" href="{{ route('meetings.show', [$event, $meeting]) }}">
                        <time datetime="{{ $scheduled->toIso8601String() }}"><strong>{{ $scheduled->format('d') }}</strong><span>{{ $scheduled->isoFormat('MMM YYYY') }}</span></time>
                        <div>
                            <h3>@display($meeting->title)</h3>
                            <p>{{ $scheduled->format('H:i') }} WITA</p>
                        </div>
                        <x-icon name="arrow" size="15" />
                    </a>
                @empty
                    <p class="ecc-quiet">{{ $archived ? 'Belum ada riwayat rapat.' : 'Belum ada rapat mendatang.' }}</p>
                @endforelse
                @if($meetingDecision)
                    <p class="ecc-decision line-clamp-3"><strong>Keputusan</strong> @display($meetingDecision->summary)</p>
                @endif
                @if(!$archived)
                    @can('manage', $event)
                        <a class="ecc-link ev-link" href="{{ route('meetings.create', $event) }}">Buat rapat teknis</a>
                    @endcan
                @endif
            </section>

            @if($archived)
                @include('events.partials.overview-tasks')
            @endif
        </div>
        <aside class="ecc-context-panel" aria-label="Konteks kegiatan">
            <section class="ecc-team">
                <div class="ecc-section-head">
                    <h2>{{ $archived ? 'Riwayat kepanitiaan' : 'Kepanitiaan' }}</h2>
                    <a class="ecc-link ev-link" href="{{ route('workspace.members', $event) }}">Lihat panitia</a>
                </div>
                @if($coordinator)
                    <div class="ecc-team-lead">
                        <span aria-hidden="true"><x-icon name="people" size="20" /></span>
                        <div>
                            <strong>@display($coordinator->user->name)</strong>
                            <p>Ketua panitia</p>
                        </div>
                    </div>
                @elseif(!$archived)
                    <p class="ecc-quiet">Ketua belum ditetapkan.</p>
                @endif
            </section>
            @if($event->eventSeries)
                <section class="ecc-series">
                    <h2>@display($event->eventSeries->name)</h2>
                    @if($previousEdition || $nextEdition)
                        <ol>
                            @if($previousEdition)
                                <li><a class="ev-link" href="{{ route('workspace.overview', $previousEdition) }}"><span>Edisi sebelumnya</span><strong>@display($previousEdition->title)</strong></a></li>
                            @endif
                            <li aria-current="step"><span>Edisi ini · {{ $start->format('Y') }}</span></li>
                            @if($nextEdition)
                                <li><a class="ev-link" href="{{ route('workspace.overview', $nextEdition) }}"><span>Edisi berikutnya</span><strong>@display($nextEdition->title)</strong></a></li>
                            @endif
                        </ol>
                    @endif
                    @can('manage', $event->organization)
                        <a class="ecc-link ev-link" href="{{ route('series.show', [$event->organization, $event->eventSeries]) }}">Riwayat seri</a>
                    @endcan
                </section>
            @endif
            @if(!$completed && $legacyCount)
                <a class="ecc-link ev-link" href="{{ route('workspace.legacy', $event) }}"><x-icon name="book" size="16" />{{ $legacyCount }} pembelajaran edisi sebelumnya</a>
            @endif
            <details class="ecc-facts">
                <summary>Detail kegiatan <x-icon name="chevron" size="15" /></summary>
                <dl>
                    <div>
                        <dt>Jam kegiatan · WITA</dt>
                        <dd><time datetime="{{ $start->toIso8601String() }}">{{ $start->format('H:i') }}</time> – <time datetime="{{ $end->toIso8601String() }}">{{ $start->isSameDay($end) ? $end->format('H:i') : $end->isoFormat('D MMM YYYY, HH:mm') }}</time></dd>
                    </div>
                    @if($event->targets->isNotEmpty())
                        <div><dt>Target program studi</dt><dd>@display($event->targets->pluck('name')->join(', '))</dd></div>
                    @endif
                    @if($event->description)
                        <div><dt>Tentang kegiatan</dt><dd>@display($event->description)</dd></div>
                    @endif
                </dl>
            </details>
            @if(!$archived)
                @can('cancel', $event)
                    <a class="ecc-cancel ev-link" href="{{ route('workspace.cancel.confirm', $event) }}">Batalkan event</a>
                @endcan
            @endif
        </aside>
    </div>
</div>
@endsection
