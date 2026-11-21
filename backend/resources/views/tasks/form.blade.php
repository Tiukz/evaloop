@extends('layouts.app')
@section('title', $task->exists ? 'Edit Tugas' : 'Buat Tugas')
@section('breadcrumb', 'Tugas & progres / Form tugas')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" active="tasks" />
<header class="page-header"><div><h1 class="page-title">{{ $task->exists ? 'Edit Tugas' : 'Buat Tugas' }}</h1></div></header>
<form method="POST" action="{{ $task->exists ? route('tasks.update', [$event, $task]) : route('tasks.store', $event) }}" class="operational-form w-full max-w-4xl">
@csrf
@if($task->exists) @method('PATCH') @endif
<x-form-section title="Pekerjaan"><x-input name="title" label="Judul tugas" :value="$task->title" required maxlength="200" /><x-select name="decision_id" label="Keputusan sumber (opsional)"><option value="">Tanpa keputusan</option>@foreach($decisions as $decision)<option value="{{ $decision->id }}" @selected((string)filter_var(old('decision_id', $task->exists ? $task->decision_id : request()->query('decision')), FILTER_VALIDATE_INT) === (string)$decision->id)>@display($decision->meeting->title) · @display(Str::limit($decision->summary, 80))</option>@endforeach</x-select></x-form-section>
<x-form-section title="Penanggung jawab & progres" description="PIC hanya dapat dipilih dari panitia event ini."><x-select name="assignee_user_id" label="PIC"><option value="">Belum ditetapkan</option>@foreach($memberships as $membership)<option value="{{ $membership->user_id }}" @selected((string)filter_var(old('assignee_user_id', $task->assignee_user_id), FILTER_VALIDATE_INT) === (string)$membership->user_id)>@display($membership->user->name)</option>@endforeach</x-select><div class="form-field-grid"><x-select name="status" label="Status" required>@foreach(['todo' => 'Belum dimulai', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $task->status ?? 'todo') === $value)>{{ $label }}</option>@endforeach</x-select><x-input name="due_at" label="Tenggat (WITA, opsional)" type="datetime-local" :value="$task->due_at?->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" /></div></x-form-section>
@if($task->source_lesson_id && $task->sourceLesson && ($task->sourceLesson->event_id === $event->id || $task->sourceLesson->isInheritedBy($event)))
    <div class="form-section">
        <a class="section-link ev-link" href="{{ route('legacy.source', [$event, $task->sourceLesson]) }}">Lihat evaluasi sumber: @display($task->sourceLesson->event->title)</a>
    </div>
@endif
<div class="form-actions"><x-button loading-label="Menyimpan…">Simpan tugas</x-button><a class="ev-link" href="{{ route('workspace.tasks', $event) }}">Kembali ke daftar tugas</a></div>
</form>
</div>
@endsection
