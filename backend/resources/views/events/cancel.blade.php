@extends('layouts.app')
@section('title', 'Pembatalan Event')
@section('breadcrumb', 'Event Workspace / Pembatalan')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" />
<header class="page-header"><div><h1 class="page-title">Pembatalan Event</h1></div></header>
<div class="form-layout"><section class="operational-form"><div class="form-section"><div class="mb-4"><x-status-badge :status="$event->status" /></div><h2>@display($event->title)</h2><p class="mt-4 text-sm leading-7 text-slate-600">Pembatalan tidak menghapus arsip kegiatan. Status event akan berubah menjadi cancelled.</p></div>
@if(in_array($event->status, ['draft', 'published'], true))<form action="{{ route('workspace.cancel', $event) }}" method="POST">@csrf<div class="form-actions"><x-button variant="danger">Batalkan event</x-button><a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali tanpa membatalkan</a></div></form>@else<p role="status" class="p-5 text-sm leading-7 text-slate-500">Pembatalan hanya tersedia untuk draft atau event yang dipublikasikan. Arsip kegiatan tetap tersimpan.</p><div class="form-actions"><a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali ke workspace</a></div>@endif</section><aside class="context-rail"><h2>Dampak perubahan</h2><p>Event cancelled tidak menjadi pembanding Collision Detection. Rapat, tugas, dan evaluasi yang sudah tercatat tetap tersimpan.</p></aside></div>
</div>
@endsection
