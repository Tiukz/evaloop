@extends('layouts.app')
@section('title', 'Publikasi Event')
@section('breadcrumb', 'Event Workspace / Publikasi')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" />
<header class="page-header"><div><h1 class="page-title">Publikasi Event</h1></div></header>
<div class="form-layout"><section class="operational-form"><div class="form-section"><div class="mb-4"><x-status-badge :status="$event->status" /></div><h2>@display($event->title)</h2><p class="mt-4 text-sm leading-7 text-slate-600">Publikasi mengubah status Draft menjadi Dipublikasikan dan menampilkan informasi kegiatan di Event Radar. Ini tidak memberikan akses publik ke workspace dan bukan izin resmi penyelenggaraan kegiatan.</p></div>
@if($event->status === 'draft')<form action="{{ route('workspace.publish', $event) }}" method="POST">@csrf<div class="form-actions"><x-button>Konfirmasi publikasi</x-button><a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali ke workspace</a></div></form>@else<p role="status" class="p-5 text-sm leading-7 text-slate-500">Event ini tidak berstatus draft. Publikasi hanya tersedia untuk draft.</p><div class="form-actions"><a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali ke workspace</a></div>@endif</section><aside class="context-rail"><h2>Periksa kesiapan jadwal</h2><p>Peringatan bentrokan membantu koordinasi. Hasilnya tidak otomatis melarang atau membatalkan kegiatan.</p><a class="section-link ev-link" href="{{ route('workspace.conflicts', $event) }}">Tinjau potensi bentrokan sebelum publikasi <x-icon name="radar" size="16" /></a></aside></div>
</div>
@endsection
