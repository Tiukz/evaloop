@extends('layouts.app')
@section('title', 'Meeting Hub')
@section('breadcrumb', 'Event Workspace / Meeting Hub')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    <x-workspace-nav :event="$event" active="meetings" :show-context-details="false" />
    <header class="page-header">
        <h1 class="page-title">Meeting Hub</h1>
        @can('manage', $event)
            <x-button :href="route('meetings.create', $event)"><span class="inline-flex items-center gap-2"><x-icon name="event" size="16" /> Buat rapat</span></x-button>
        @endcan
    </header>

    <x-list-search :action="route('workspace.meetings', $event)" label="Cari judul atau catatan rapat" :value="$search" class="mb-5" />

    @if($meetings->isEmpty())
        <section class="rounded-xl border border-ev-border bg-white p-5">
            @if($meetings->total())
                <h2>Halaman ini tidak berisi rapat</h2>
                <a class="section-link ev-link mt-1" href="{{ $meetings->url(1) }}">Buka halaman pertama</a>
            @elseif($search !== '')
                <h2>Tidak ada rapat yang cocok.</h2>
            @else
                <h2>Belum ada rapat</h2>
                <p class="mt-1 text-sm text-slate-600">Rapat dan catatannya akan tampil di sini.</p>
            @endif
        </section>
    @else
        @php
            $referenceTime = now();
            $agendaGroups = $meetings->getCollection()->groupBy(fn ($meeting) => $meeting->scheduled_at->gte($referenceTime) ? 'upcoming' : 'past');
        @endphp
        <div class="space-y-7">
            @foreach(['upcoming' => 'Mendatang', 'past' => 'Jadwal sebelumnya'] as $key => $heading)
                @if(isset($agendaGroups[$key]))
                    <section aria-labelledby="meeting-group-{{ $key }}">
                        <h2 id="meeting-group-{{ $key }}" class="mb-2">{{ $heading }}</h2>
                        <ul class="divide-y divide-ev-border rounded-xl border border-ev-border bg-white">
                            @foreach($key === 'upcoming' ? $agendaGroups[$key]->sortBy('scheduled_at') : $agendaGroups[$key] as $meeting)
                                @php
                                    $scheduled = $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->locale('id');
                                    $excerpt = Str::limit(Str::squish($meeting->notes ?? ''), 240);
                                @endphp
                                <li>
                                    <a href="{{ route('meetings.show', [$event, $meeting]) }}"
                                       aria-labelledby="meeting-action-{{ $meeting->id }} meeting-title-{{ $meeting->id }}"
                                       aria-describedby="meeting-meta-{{ $meeting->id }}"
                                       class="group grid min-w-0 items-center gap-x-8 gap-y-3 rounded-lg px-[clamp(1rem,2.5vw,1.75rem)] py-4 transition-colors hover:bg-ev-ice focus-visible:bg-ev-ice focus-visible:-outline-offset-2 motion-reduce:transition-none @min-[38rem]:grid-cols-[minmax(0,1fr)_auto]">
                                        <div class="min-w-0">
                                            <h3 id="meeting-title-{{ $meeting->id }}" class="wrap-anywhere text-[clamp(1.125rem,1.04rem_+.4vw,1.25rem)]!">@display($meeting->title)</h3>
                                            <div id="meeting-meta-{{ $meeting->id }}" class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-sm text-ev-neutral-ink">
                                                <time datetime="{{ $scheduled->toIso8601String() }}">{{ $scheduled->isoFormat('D MMMM YYYY · HH.mm') }} WITA</time>
                                                @if($meeting->decisions_count)
                                                    <span>{{ $meeting->decisions_count }} keputusan</span>
                                                @endif
                                            </div>
                                            @if(filled($excerpt))
                                                <p class="mt-2 max-w-[74ch] text-[0.9375rem] leading-relaxed wrap-anywhere text-ev-ink">@display($excerpt)</p>
                                            @else
                                                <p class="mt-2 text-sm text-slate-600">Belum ada catatan.</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center gap-2 justify-self-start text-sm font-semibold text-ev-primary @min-[38rem]:justify-self-end">
                                            <span id="meeting-action-{{ $meeting->id }}">Buka catatan</span>
                                            <x-icon name="arrow" size="16" class="motion-safe:transition-transform motion-safe:group-hover:translate-x-0.5" />
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach
        </div>
        <div class="mt-6">{{ $meetings->links() }}</div>
    @endif
</div>
@endsection
