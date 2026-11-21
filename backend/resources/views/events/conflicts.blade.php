@extends('layouts.app')
@section('title')
    Jadwal & bentrokan
@endsection
@section('breadcrumb')
    Event Workspace / Jadwal & bentrokan
@endsection
@section('content')
@include('events.partials.workspace-module-styles')
<style>
    body:has(.collision-page) .topbar-breadcrumb > span { white-space: normal; }
    body:has(.collision-page) .topbar-discovery { flex-shrink: 0; white-space: nowrap; }
</style>
<div class="workspace-module collision-page">
    <x-workspace-nav :event="$event" active="conflicts" :show-context-details="false" />

    <header class="page-header"><h1 class="page-title">Jadwal & bentrokan</h1></header>

    <section aria-label="Jadwal yang diperiksa" class="mb-8 rounded-xl border border-ev-border bg-ev-ice px-4 py-3 @min-[40rem]:px-5">
        <dl class="grid gap-x-6 gap-y-3 @min-[40rem]:grid-cols-[minmax(0,1.2fr)_minmax(0,.9fr)_minmax(0,1fr)]">
            <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-600">Jadwal kegiatan</dt>
                <dd class="mt-1 wrap-anywhere text-sm font-semibold text-ev-info"><x-event-time-range :event="$event" /></dd>
            </div>
            <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-600">Lokasi</dt>
                <dd class="mt-1 wrap-anywhere text-sm text-ev-ink">@display(trim($event->venue ?? '') ?: 'Belum ditentukan')</dd>
            </div>
            <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-600">Target program studi</dt>
                <dd class="mt-1 wrap-anywhere text-sm text-ev-ink">@display($event->targets->pluck('name')->join(', ') ?: 'Belum ditentukan')</dd>
            </div>
        </dl>
    </section>

    <section aria-labelledby="collision-results-heading">
        @if($unavailableReason !== null)
            <h2 id="collision-results-heading" class="type-section">Jadwal belum diperiksa</h2>
            <p class="mt-2 border-s-2 border-ev-warning ps-3 text-sm leading-6 text-slate-700">{{ $unavailableReason }}</p>
        @elseif($comparisons->total() === 0)
            <h2 id="collision-results-heading" class="type-section">Tidak ada potensi bentrokan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Tidak ada jadwal yang beririsan dalam kegiatan publik yang diperiksa.</p>
        @else
            <h2 id="collision-results-heading" class="type-section">{{ $comparisons->total() }} jadwal beririsan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Irisan waktu memberi konteks jadwal. Lokasi atau target yang sama perlu ditinjau lebih lanjut.</p>
            @if($warnings)
                <ul role="list" class="mt-4 divide-y divide-ev-border rounded-xl border border-ev-border bg-ev-surface">
                    @foreach($warnings as $warning)
                        <x-collision-warning :event="$warning['event']" :types="$warning['types']" />
                    @endforeach
                </ul>
                @if($comparisons->hasPages())
                    <div class="mt-5 [&_nav_p]:hidden">{{ $comparisons->links() }}</div>
                @endif
            @else
                <p class="mt-4 text-sm">Halaman ini tidak berisi hasil. <a class="ev-link font-medium text-ev-primary underline" href="{{ $comparisons->url(1) }}">Buka halaman pertama</a>.</p>
            @endif
        @endif
    </section>
</div>
@endsection
