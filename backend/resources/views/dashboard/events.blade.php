@extends('layouts.app')
@section('title', 'Kegiatan Saya')
@section('breadcrumb', 'Ruang kerja / Kegiatan Saya')
@section('content')
<header class="page-header">
    <div>
        <h1 class="page-title">Kegiatan Saya</h1>
    </div>
</header>

@if($featuredEvent)
    <section aria-labelledby="featured-workspace" class="@container mb-6 rounded-2xl border border-ev-border bg-linear-to-br from-ev-ice via-white to-ev-aqua p-[clamp(1rem,3vw,2rem)]">
        <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5">
            <div class="min-w-0 flex-1 basis-60">
                <p class="mb-3 text-sm font-semibold text-ev-primary">Workspace utama</p>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 id="featured-workspace" class="text-[clamp(1.5rem,3vw,2.5rem)] leading-tight font-semibold tracking-tight wrap-anywhere">@display($featuredEvent->title)</h2>
                    <x-status-badge :status="$featuredEvent->status" />
                </div>
                <p class="mt-3 text-sm font-semibold wrap-anywhere">@display($featuredEvent->organization->name)</p>
                <time class="mt-1 block text-sm text-slate-600" datetime="{{ $featuredEvent->starts_at->toIso8601String() }}">{{ $featuredEvent->starts_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA</time>
            </div>
            <div class="flex flex-wrap items-center gap-1">
                <x-button :href="route('workspace.overview', $featuredEvent)"><span class="inline-flex items-center gap-2">Buka workspace <x-icon name="arrow" size="16" /></span></x-button>
                <x-event-status-guidance :event="$featuredEvent" compact />
            </div>
        </div>
    </section>

    <section aria-labelledby="workspace-directory">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 id="workspace-directory" class="text-xl font-bold tracking-tight">Daftar workspace</h2>
            <p class="text-sm text-slate-600">{{ $events->total() }} workspace dapat diakses</p>
        </div>
        <x-list-search :action="route('my.events')" label="Cari event atau organisasi" :value="$search" class="mb-4" />
        @if($events->isEmpty())
            @if($search !== '' && !$events->total())
                <p class="py-4 text-sm text-slate-600">Tidak ada event yang cocok.</p>
            @else
                <p class="py-4 text-sm text-slate-600">Tidak ada workspace di halaman ini. <a class="section-link ev-link" href="{{ $events->url(1) }}">Ke halaman pertama</a></p>
            @endif
        @else
            <ul class="@container divide-y divide-ev-border rounded-2xl border border-ev-border bg-white p-1">
                @foreach($events as $event)
                    <li class="grid items-center gap-x-8 gap-y-3 px-[clamp(.75rem,2vw,1.25rem)] py-3 transition-colors hover:bg-ev-ice focus-within:bg-ev-ice motion-reduce:transition-none @min-[48rem]:grid-cols-[minmax(0,1fr)_auto]">
                        <div class="min-w-0">
                            <h3 id="workspace-title-{{ $event->id }}" class="type-card wrap-anywhere">
                                <a class="ev-link" href="{{ route('workspace.overview', $event) }}">@display($event->title)</a>
                            </h3>
                            <div class="type-metadata mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-slate-600">
                                <span class="max-w-full wrap-anywhere">@display($event->organization->name)</span>
                                <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA</time>
                            </div>
                        </div>
                        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2 @min-[32rem]:gap-x-5 @min-[48rem]:grid @min-[48rem]:grid-cols-[7.75rem_auto_6.5rem] @min-[48rem]:gap-x-6">
                            <div class="basis-full @min-[32rem]:basis-auto">
                                <x-status-badge :status="$event->status" />
                            </div>
                            <x-button variant="secondary" size="sm" :href="route('workspace.overview', $event)"
                                      aria-labelledby="workspace-action-{{ $event->id }} workspace-title-{{ $event->id }}">
                                <span class="inline-flex items-center gap-2 whitespace-nowrap">
                                    <span id="workspace-action-{{ $event->id }}">Buka workspace</span>
                                    <x-icon name="arrow" size="15" class="hidden @min-[32rem]:block" />
                                </span>
                            </x-button>
                            <x-event-status-guidance :event="$event" compact subtle class="justify-self-end" />
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $events->links() }}</div>
        @endif
    </section>
@else
    <x-empty-state title="Belum ada workspace yang diizinkan" message="Workspace tersedia untuk panitia event dan admin organisasi dengan keanggotaan yang berlaku.">
        @if($isInstitutionAdmin)
            <p class="mt-3 text-sm text-slate-600">Admin platform/universitas tidak otomatis memiliki akses ke workspace privat.</p>
        @endif
        <a class="section-link ev-link mt-3" href="{{ route($isInstitutionAdmin ? 'dashboard' : 'events.index') }}">{{ $isInstitutionAdmin ? 'Kembali ke dashboard administrasi' : 'Jelajahi Event Radar' }}</a>
    </x-empty-state>
@endif
@endsection
