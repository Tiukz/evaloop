@extends('layouts.app')
@section('title', $event->exists ? 'Edit Event' : 'Buat Event')
@section('breadcrumb', 'Event / Informasi kegiatan')
@section('content')
@if($event->exists)
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
@endif
@if($event->exists)<x-workspace-nav :event="$event" />@else<x-organization-nav :organization="$organization" active="events" />@endif
<header class="page-header">
    @if($event->exists)
        <div><h1 class="page-title">Edit Event</h1><p class="page-description">@display($organization->name)</p></div>
    @else
        <h2 class="page-title">Buat Event</h2>
    @endif
</header>
<div class="form-layout"><form action="{{ $event->exists ? route('workspace.update', $event) : route('organization.events.store', $organization) }}" method="POST" enctype="multipart/form-data" class="operational-form">@csrf @if($event->exists) @method('PATCH') @endif
@if($event->exists) @include('events.partials.poster-field') @endif
<x-form-section title="Identitas kegiatan"><x-input name="title" label="Nama event" :value="$event->title" required maxlength="200" /><x-select name="event_series_id" label="Event Series"><option value="">Event satu kali (tanpa seri)</option>@foreach($series as $item)<option value="{{ $item->id }}" @selected((string)filter_var(old('event_series_id', $event->event_series_id), FILTER_VALIDATE_INT) === (string)$item->id)>@display($item->name)</option>@endforeach</x-select><x-textarea name="description" label="Deskripsi publik" :value="$event->description" rows="5" maxlength="20000" /></x-form-section>
@unless($event->exists) @include('events.partials.poster-field') @endunless
<x-form-section title="Jadwal & lokasi" description="Gunakan waktu WITA. Waktu selesai harus sesudah waktu mulai."><div class="form-field-grid"><x-input name="starts_at" label="Mulai (WITA)" type="datetime-local" :value="$event->starts_at?->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" required /><x-input name="ends_at" label="Selesai (WITA)" type="datetime-local" :value="$event->ends_at?->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" required /></div><x-input name="venue" label="Lokasi" :value="$event->venue" maxlength="160" /></x-form-section>
<x-form-section title="Target peserta" description="Pilih program studi yang dituju. Target ini juga digunakan dalam pemeriksaan potensi bentrokan peserta.">@php($selectedProgramIds = collect((array)old('target_program_ids', $event->exists ? $event->targets->modelKeys() : []))->filter(fn ($id) => is_int($id) || is_string($id))->map(fn ($id) => (string)$id)->all())<x-select name="target_program_ids[]" label="Target program studi (dapat memilih lebih dari satu)" multiple size="5">@foreach($programs as $program)<option value="{{ $program->id }}" @selected(in_array((string)$program->id, $selectedProgramIds, true))>@display($program->name)</option>@endforeach</x-select></x-form-section>
<div class="form-actions"><x-button loading-label="Menyimpan…">Simpan</x-button><a class="ev-link" href="{{ $event->exists ? route('workspace.overview', $event) : route('organizations.events', $organization) }}">Kembali ke event / organisasi</a></div></form><aside class="context-rail"><h2>Sebelum publikasi</h2><p>Publikasi dan perubahan status merupakan aksi terpisah oleh pihak berwenang.</p>@if($event->exists)<a class="action-line ev-link" href="{{ route('workspace.conflicts', $event) }}">Tinjau jadwal tersimpan <x-icon name="radar" size="16" /></a><p>Perubahan form yang belum disimpan belum diperiksa oleh Collision Detection.</p>@else<p>Event baru dimulai sebagai draft. Simpan untuk memeriksa potensi bentrokan dengan kegiatan publik lain.</p>@endif</aside></div>
@if($event->exists)</div>@endif
@endsection
