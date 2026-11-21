@extends('layouts.app')
@section('title', 'Event Radar')
@section('content')
<div class="radar-page">
@php
    $clean = fn ($text) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $text ?? '');
    $activeFilters = array_diff_key($filters, ['page' => true, 'period' => true]);
@endphp
<form method="GET" action="{{ route('events.index') }}" aria-label="Filter kegiatan" class="radar-discovery-form">
    @if($period !== 'all')<input type="hidden" name="period" value="{{ $period }}">@endif
    <section class="radar-editorial-hero" aria-labelledby="radar-hero-title">
        <div class="radar-hero-main"><header class="radar-discovery-header"><h1 id="radar-hero-title">Ada apa di kampus?</h1></header><div class="radar-search-slot">
    <div class="radar-discovery-query"><div class="radar-query-field"><x-icon name="search" size="22" /><label for="q" class="sr-only">Cari nama kegiatan</label><input id="q" name="q" type="search" value="{{ is_string(old('q', $filters['q'] ?? '')) ? old('q', $filters['q'] ?? '') : '' }}" maxlength="120" placeholder="Cari kegiatan…" @error('q') aria-invalid="true" aria-describedby="q-error" @enderror></div><x-button loading-label="Mencari…"><span class="inline-flex items-center gap-2">Cari <x-icon name="arrow" size="16" /></span></x-button></div>
    @error('q')<p id="q-error" role="alert" class="mt-2 text-sm text-ev-danger">{{ $message }}</p>@enderror
        </div></div>
        <x-radar-featured :event="$featured" />
    </section>
    <section class="radar-discovery-toolbar" aria-label="Jelajahi dan filter kegiatan">
<div class="radar-browse-toolbar"><nav aria-label="Periode kegiatan" class="radar-period-tabs">@foreach(['upcoming' => 'Mendatang', 'all' => 'Semua', 'history' => 'Riwayat'] as $value => $label)<a href="{{ route('events.index', array_replace(array_diff_key($filters, ['page' => true]), ['period' => $value])) }}" @if($period === $value) aria-current="page" @endif class="ev-link">{{ $label }}</a>@endforeach</nav><p role="status" aria-label="{{ $events->total() }} kegiatan publik ditemukan">{{ $events->total() }} kegiatan</p></div>
    <div class="radar-filter-bar"><span class="radar-filter-caption">Saring menurut</span>
    <x-filter-menu label="Fakultas" :active="isset($filters['faculty_id'])" :value="$clean($faculties->get((int)($filters['faculty_id'] ?? 0))?->name)" :open="$errors->has('faculty_id')" :clear-url="route('events.index', array_diff_key($filters, ['faculty_id' => true, 'page' => true]))">
<x-select name="faculty_id" label="Fakultas penyelenggara / target">
            <option value="">Semua fakultas</option>
            @foreach($faculties as $faculty)
                <option value="{{ $faculty->id }}" @selected((int)filter_var(old('faculty_id', $filters['faculty_id'] ?? ''), FILTER_VALIDATE_INT) === $faculty->id)>@display($clean($faculty->name))</option>
            @endforeach
        </x-select>
    <p class="filter-panel-hint">Mencocokkan fakultas penyelenggara atau target peserta.</p></x-filter-menu>
    <x-filter-menu label="Program studi" :active="isset($filters['study_program_id'])" :value="$clean($programs->firstWhere('id', (int)($filters['study_program_id'] ?? 0))?->name)" :open="$errors->has('study_program_id')" :clear-url="route('events.index', array_diff_key($filters, ['study_program_id' => true, 'page' => true]))">
<x-select name="study_program_id" label="Program studi penyelenggara / target">
            <option value="">Semua program studi</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected((int)filter_var(old('study_program_id', $filters['study_program_id'] ?? ''), FILTER_VALIDATE_INT) === $program->id)>@display($clean($program->name)) · @display($clean($faculties->get($program->faculty_id)?->name))</option>
            @endforeach
        </x-select>
    <p class="filter-panel-hint">Jika fakultas dipilih, program studi harus sesuai fakultas tersebut.</p></x-filter-menu>
    <x-filter-menu label="Organisasi" :active="isset($filters['organization_id'])" :value="$clean($organizations->firstWhere('id', (int)($filters['organization_id'] ?? 0))?->name)" :open="$errors->has('organization_id')" :clear-url="route('events.index', array_diff_key($filters, ['organization_id' => true, 'page' => true]))">
<x-select name="organization_id" label="Organisasi penyelenggara">
            <option value="">Semua organisasi publik</option>
            @foreach($organizations as $organization)
                <option value="{{ $organization->id }}" @selected((int)filter_var(old('organization_id', $filters['organization_id'] ?? ''), FILTER_VALIDATE_INT) === $organization->id)>@display($clean($organization->name))</option>
            @endforeach
        </x-select>
    </x-filter-menu>
    <x-filter-menu label="Tanggal" :active="isset($filters['date_from']) || isset($filters['date_to'])" :value="($filters['date_from'] ?? 'Awal') . ' – ' . ($filters['date_to'] ?? 'Akhir')" :open="$errors->has('date_from') || $errors->has('date_to')" :clear-url="route('events.index', array_diff_key($filters, ['date_from' => true, 'date_to' => true, 'page' => true]))" wide>
    <div class="filter-date-fields"><x-input name="date_from" label="Mulai rentang tanggal (WITA)" type="date" :value="$filters['date_from'] ?? ''" /><x-input name="date_to" label="Akhir rentang tanggal (WITA)" type="date" :value="$filters['date_to'] ?? ''" /></div><p class="filter-panel-hint">Kegiatan yang berlangsung dalam rentang hari tersebut, menggunakan WITA.</p>
    </x-filter-menu>
    @if(count($activeFilters))<a class="filter-reset ev-link" href="{{ route('events.index', $period !== 'all' ? ['period' => $period] : []) }}">Reset filter</a>@endif
    </div>
    </section>
</form>

@if($events->isEmpty())
    @if($events->total() > 0)<x-empty-state title="Halaman hasil tidak tersedia" message="Kegiatan ditemukan pada halaman sebelumnya."><a class="section-link ev-link" href="{{ $events->url(1) }}">Kembali ke halaman pertama</a></x-empty-state>
    @elseif(count($activeFilters))<x-empty-state title="Tidak ada kegiatan sesuai filter" message="Coba perluas rentang tanggal atau hapus sebagian filter. Draft dan informasi privat tidak ditampilkan."><a class="section-link ev-link mt-4" href="{{ route('events.index') }}">Lihat semua kegiatan publik</a></x-empty-state>
    @else<x-empty-state :title="$period === 'all' ? 'Belum ada event publik' : ($period === 'upcoming' ? 'Belum ada kegiatan mendatang' : 'Belum ada riwayat kegiatan')" message="Kegiatan yang dipublikasikan organisasi akan muncul sesuai periode yang dipilih."><a class="section-link ev-link mt-4" href="{{ route('events.index', ['period' => 'all']) }}">Lihat semua kegiatan</a></x-empty-state>@endif
@else
    @php
        [$verificationEvents, $discoveryEvents] = $events->getCollection()->partition(fn($event) => preg_match('/^\[SIMULASI\] Event Verifikasi \d{8}-\d{6}$/u', $event->title));
        $groups = $discoveryEvents->groupBy(fn($event) => $event->status === 'published' && $event->ends_at->gt($radarNow) ? 'upcoming' : 'history');
    @endphp
    @foreach(['upcoming' => 'Mendatang & sedang berlangsung', 'history' => 'Riwayat kegiatan'] as $key => $heading)
    @if(isset($groups[$key]))<section class="radar-event-section" aria-labelledby="radar-group-{{ $key }}"><div class="radar-group-heading"><h2 id="radar-group-{{ $key }}">{{ $heading }}</h2></div><div class="radar-event-list">@foreach($groups[$key] as $event)<x-event-card :event="$event" :reference-time="$radarNow" />@endforeach</div></section>@endif
    @endforeach
    @if($verificationEvents->isNotEmpty())<details class="radar-verification"><summary>Data verifikasi teknis <x-icon name="chevron" size="16" /></summary><p class="mb-4 text-sm leading-6 text-slate-600">Data pemeriksaan alur aplikasi.</p><div class="radar-event-list">@foreach($verificationEvents as $event)<x-event-card :event="$event" :reference-time="$radarNow" />@endforeach</div></details>@endif
    <div class="mt-6">{{ $events->links() }}</div>
@endif
</div>
@endsection
