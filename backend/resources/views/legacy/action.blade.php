@extends('layouts.app')
@section('title', 'Buat Tindak Lanjut')
@section('breadcrumb', 'Legacy Insights / Buat Tindak Lanjut')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" active="legacy" />
<header class="page-header"><div><h1 class="page-title">Buat Tindak Lanjut</h1></div><a class="section-link ev-link" href="{{ route('workspace.legacy', $event) }}">Kembali ke Legacy Insights</a></header>
<x-legacy-lineage :event="$event" :source="$lesson->event" />
<div class="legacy-compose"><aside class="legacy-source-panel"><h2 class="text-base font-semibold">Sumber: @display($lesson->event->title)</h2><x-lesson-content :lesson="$lesson" /></aside><section class="min-w-0" aria-labelledby="action-heading"><div class="mb-5"><p class="identity-scope">Event penerima</p><h2 id="action-heading" class="text-xl font-semibold">@display($event->title)</h2></div>
@if($existing)
<div class="operational-form p-6"><p class="mb-4 text-sm leading-6">Tindak lanjut untuk pembelajaran ini sudah tersedia. Tidak akan dibuat duplikat.</p><x-legacy-action :event="$event" :task="$existing" /><x-button variant="secondary" :href="route('tasks.edit', [$event, $existing])" class="mt-5">Buka tugas tindak lanjut</x-button></div>
@else
<form method="POST" action="{{ route('legacy.action.store', [$event, $lesson]) }}" class="operational-form">@csrf
<x-form-section title="Tindakan yang akan dilakukan" description="Tugas disimpan pada edisi penerima; evaluasi sumber tetap menjadi milik edisi sebelumnya."><x-input name="title" label="Judul tugas" required maxlength="200" :value="Str::limit($lesson->recommendation, 190, '')" /></x-form-section>
<x-form-section title="Penanggung jawab & tenggat" description="Pilih panitia edisi ini dan tenggat sebelum kegiatan dimulai."><div class="form-fields"><x-select name="assignee_user_id" label="PIC dari kepanitiaan edisi ini" required><option value="">Pilih anggota</option>@foreach($memberships as $membership)<option value="{{ $membership->user_id }}" @selected((string)filter_var(old('assignee_user_id'), FILTER_VALIDATE_INT) === (string)$membership->user_id)>@display($membership->user->name)</option>@endforeach</x-select><x-input name="due_at" label="Tenggat sebelum event dimulai (WITA)" type="datetime-local" required /></div></x-form-section>
<div class="form-actions"><p class="max-w-sm text-xs leading-5 text-slate-500">Status awal: Belum dimulai. Tugas hanya dibuat setelah Anda menyimpannya.</p><x-button>Simpan tindak lanjut</x-button></div>
</form>
@endif
</section></div>
</div>
@endsection
