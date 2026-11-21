@extends('layouts.app')
@section('title', $series->name)
@section('breadcrumb', 'Event Series / Riwayat edisi')
@section('content')
<x-organization-nav :organization="$organization" active="series" />
<header class="page-header"><div><p class="mb-3 text-xs font-medium text-ev-primary">Riwayat penyelenggaraan</p><h2 class="page-title">@display($series->name)</h2><p class="page-description">@display($organization->name)</p></div><a class="section-link ev-link" href="{{ route('series.index', $organization) }}">Kembali ke Event Series <x-icon name="arrow" size="15" /></a></header>
<div class="reading-layout"><div class="min-w-0">
@if($events->isEmpty())<x-empty-state title="Belum ada edisi" message="Buat event dan pilih seri ini untuk menghubungkan penyelenggaraannya." />@else
<ol class="edition-timeline">@foreach($events as $event)<li class="edition-entry"><time class="edition-year" datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->timezone('Asia/Makassar')->format('Y') }}</time><article class="edition-body"><div class="mb-3"><x-status-badge :status="$event->status" /></div><h2 class="text-lg font-semibold"><a class="ev-link" href="{{ route('workspace.overview', $event) }}">@display($event->title)</a></h2><p class="mt-3 text-sm text-slate-500">{{ $event->starts_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA</p><p class="mt-2 text-xs text-slate-500">@display($event->venue ?: 'Lokasi belum ditentukan')</p><a class="section-link ev-link mt-3" href="{{ route('workspace.overview', $event) }}">Buka workspace edisi <x-icon name="arrow" size="15" /></a></article></li>@endforeach</ol><div class="mt-6">{{ $events->links() }}</div>@endif
</div><aside class="context-rail"><h2>Hubungan antaredisi</h2><p>Anggota, rapat, keputusan, tugas, dan evaluasi setiap edisi tetap terpisah. Tidak ada penyalinan otomatis.</p><p>Pembelajaran yang disetujui dapat dibaca pada edisi berikutnya yang berwenang melalui Legacy Insights.</p><a class="section-link ev-link" href="{{ route('organization.events.create', $organization) }}">Buat edisi baru <x-icon name="arrow" size="15" /></a></aside></div>
@endsection
