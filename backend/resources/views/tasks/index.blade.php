@extends('layouts.app')
@section('title', 'Tugas Event')
@section('breadcrumb', 'Event Workspace / Tugas & progres')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" active="tasks" />
<header class="page-header"><div><h1 class="page-title">Tugas & progres</h1></div>@if($canManage)<x-button :href="route('tasks.create', $event)">Buat tugas <x-icon name="task" size="16" /></x-button>@endif</header>
<section class="progress-workbench" aria-label="Progres tugas event">
    <x-progress-strip :todo="$counts['todo'] ?? 0" :in-progress="$counts['in_progress'] ?? 0" :done="$counts['done'] ?? 0" />
    @if($overdue)
        <p class="text-xs font-medium text-ev-warning">{{ $overdue }} Lewat tenggat · perlu ditinjau</p>
    @endif
</section>
@if($tasks->isEmpty())<x-empty-state title="Belum ada tugas pada halaman ini" message="Tugas dan PIC untuk edisi ini akan muncul di sini.">@if($canManage)<a class="section-link ev-link mt-3" href="{{ route('tasks.create', $event) }}">Buat tugas pertama <x-icon name="arrow" size="15" /></a>@endif</x-empty-state>
@else
@php($taskGroups = $tasks->getCollection()->groupBy('status'))
<div class="space-y-6">@foreach(['todo' => 'Belum dimulai', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'] as $value => $label)@if(isset($taskGroups[$value]))<section aria-label="{{ $label }}"><div class="task-group-heading"><h2>{{ $label }}</h2><span>{{ $taskGroups[$value]->count() }} pada halaman ini</span></div><div class="issue-list">@foreach($taskGroups[$value] as $task)<x-task-card :task="$task" :can-manage="$canManage" />@endforeach</div></section>@endif @endforeach</div>
@endif
<div class="mt-6">{{ $tasks->links() }}</div>
</div>
@endsection
