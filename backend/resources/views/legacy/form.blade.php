@extends('layouts.app')
@section('title', $lesson->exists ? 'Edit Evaluasi' : 'Buat Evaluasi')
@section('breadcrumb', 'Legacy Insights / Evaluasi')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" active="legacy" />
<header class="page-header"><div><h1 class="page-title">{{ $lesson->exists ? 'Edit Evaluasi' : 'Buat Evaluasi' }}</h1></div></header>
<div class="form-layout"><div class="min-w-0 space-y-5"><form method="POST" action="{{ $lesson->exists ? route('evaluations.update', [$event, $lesson]) : route('evaluations.store', $event) }}" class="operational-form" @if(!$lesson->exists) enctype="multipart/form-data" @endif>
@csrf @if($lesson->exists) @method('PATCH') @endif
<x-form-section title="Apa yang perlu diperbaiki?"><x-textarea name="problem" label="Permasalahan" :value="$lesson->problem" rows="6" required maxlength="15000" /></x-form-section>
<x-form-section title="Apa langkah berikutnya?"><x-textarea name="recommendation" label="Rekomendasi" :value="$lesson->recommendation" rows="6" required maxlength="15000" /></x-form-section>
@if(!$lesson->exists)
    <x-form-section title="Lampiran evaluasi">
        <div class="space-y-2">
            @include('legacy.attachment-input', ['required' => false])
        </div>
    </x-form-section>
@endif
<div class="form-actions"><x-button loading-label="Menyimpan…">Simpan evaluasi</x-button><a class="section-link ev-link" href="{{ route('workspace.legacy', $event) }}">Kembali</a></div>
</form>
@if($lesson->exists)
    <section id="lesson-attachments" aria-label="Lampiran evaluasi" class="scroll-mt-24 rounded-xl border border-ev-border bg-ev-surface p-4 sm:p-5">
        <x-lesson-attachments :event="$event" :lesson="$lesson" editable />
        @if($lesson->attachments->count() < 5)
            <form method="POST" action="{{ route('evaluations.attachments.store', [$event, $lesson]) }}" enctype="multipart/form-data" class="mt-4 space-y-2">
                @csrf
                @include('legacy.attachment-input', ['required' => true])
                <x-button loading-label="Mengunggah…">Unggah lampiran</x-button>
            </form>
        @endif
    </section>
@endif
</div><aside class="context-rail"><h2>Dari evaluasi ke pembelajaran</h2><ol class="evaluation-steps"><li><strong>Catat pengalaman</strong><span>Evaluasi tersimpan internal pada edisi ini.</span></li><li><strong>Ditinjau admin organisasi</strong><span>Lampiran yang disetujui bersama evaluasi dapat dibuka oleh panitia edisi berikutnya yang berwenang. Setelah disetujui, evaluasi dan lampirannya tidak dapat diubah.</span></li><li><strong>Jadi tindakan berikutnya</strong><span>Ketua edisi berikutnya dapat membuat tugas secara eksplisit dari rekomendasi.</span></li></ol></aside></div>
</div>
@endsection
