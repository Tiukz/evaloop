@extends('layouts.app')
@section('title', 'Tugas Saya')
@section('breadcrumb', 'Ruang kerja / Tugas Saya')
@section('content')
@include('dashboard.partials.task-tracker-styles')
@php
    $labels = ['todo' => 'Belum dimulai', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'];
    $clean = fn ($value) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $value ?? '');
    $filtered = $filters['q'] !== '' || $filters['status'] !== '' || $filters['event'] !== '';
@endphp
<div class="my-task-database">
    <header class="tdb-header"><div><h1>Tugas Saya</h1></div><a class="tdb-text-link ev-link" href="{{ route('my.events') }}">Kegiatan Saya <x-icon name="arrow" size="15" /></a></header>
    
    <div class="tdb-views"><nav aria-label="Tampilan tugas">@foreach(['all' => 'Semua tugas', 'active' => 'Aktif', 'done' => 'Selesai'] as $value => $label)<a href="{{ route('my.tasks', array_merge(Arr::except($filters, ['page', 'status']), ['view' => $value])) }}" @class(['tdb-view', 'tdb-view-selected' => $filters['view'] === $value]) @if($filters['view'] === $value) aria-current="page" @endif>@if($value === 'all')<x-icon name="task" size="16" />@endif{{ $label }}</a>@endforeach</nav><span class="tdb-count" aria-live="polite">{{ $tasks->total() }} tugas</span></div>
    <form method="GET" action="{{ route('my.tasks') }}" class="tdb-toolbar" aria-label="Cari dan filter tugas">
        <input type="hidden" name="view" value="{{ $filters['view'] }}">
        <div class="tdb-search"><x-icon name="search" size="17" /><label for="task-search" class="sr-only">Cari nama tugas</label><input type="search" id="task-search" name="q" value="{{ $filters['q'] }}" placeholder="Cari tugas…" maxlength="120"></div>
        <div class="tdb-filter"><label for="task-status-filter">Status</label><select id="task-status-filter" name="status"><option value="">Semua status</option>@foreach($labels as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="tdb-filter tdb-filter-event"><label for="task-event-filter">Event</label><select id="task-event-filter" name="event"><option value="">Semua event</option>@foreach($filterEvents as $event)<option value="{{ $event->id }}" @selected((string) $filters['event'] === (string) $event->id)>@display($clean($event->title))</option>@endforeach</select></div>
        <div class="tdb-filter"><label for="task-sort">Tenggat</label><select id="task-sort" name="sort"><option value="deadline_asc" @selected($filters['sort'] === 'deadline_asc')>Terdekat dahulu</option><option value="deadline_desc" @selected($filters['sort'] === 'deadline_desc')>Terjauh dahulu</option></select></div>
        <x-button variant="secondary" size="sm">Terapkan</x-button>
        @if($filtered)<a class="tdb-text-link ev-link" href="{{ route('my.tasks', ['view' => $filters['view'], 'sort' => $filters['sort']]) }}">Reset</a>@endif
    </form>
    @if($tasks->isNotEmpty())
    <div class="tdb-table-region" role="region" aria-label="Database tugas pribadi">
        <table class="tdb-table"><caption class="sr-only">Tugas yang ditetapkan kepada Anda pada workspace yang dapat diakses.</caption><colgroup><col class="tdb-col-name"><col class="tdb-col-status"><col class="tdb-col-event"><col class="tdb-col-deadline"><col class="tdb-col-action"></colgroup><thead><tr><th scope="col">Nama tugas</th><th scope="col">Status</th><th scope="col">Event</th><th scope="col">Tenggat</th><th scope="col">Tindakan</th></tr></thead><tbody>
        @foreach($tasks as $task)
            @php
                $late = $task->status !== 'done' && $task->due_at?->isPast();
                $canManage = in_array($task->event_id, $managedEventIds);
                $detailUrl = route('workspace.tasks', [$task->event, 'task' => $task->id]).'#task-'.$task->id;
            @endphp
            <tr id="personal-task-{{ $task->id }}">
                <th scope="row" class="tdb-name-cell"><div class="tdb-task-name"><x-icon name="task" size="17" /><a class="ev-link" href="{{ $detailUrl }}">@display($clean($task->title))</a></div>
                    @if($task->source_lesson_id)
                        @if($task->sourceLesson && ($task->sourceLesson->event_id === $task->event_id || $task->sourceLesson->isInheritedBy($task->event)))<a class="tdb-legacy ev-link" href="{{ route('legacy.source', [$task->event, $task->sourceLesson]) }}"><x-icon name="book" size="13" />Sumber legacy: @display($clean($task->sourceLesson->event->title))</a>@else<p class="tdb-legacy">Sumber legacy tersimpan; akses sumber tidak tersedia pada konteks event ini.</p>@endif
                    @endif
                </th>
                <td class="tdb-status-cell" data-label="Status">
                    @if($canManage || $task->assignee_user_id === auth()->id())
                    <details class="tdb-status-menu" data-filter-menu><summary aria-label="Ubah status @display($clean($task->title))"><span class="tdb-pill tdb-pill-{{ $task->status }}"><span aria-hidden="true"></span>{{ $labels[$task->status] }}</span><x-icon name="chevron" size="13" /></summary><div class="ev-menu-panel tdb-status-panel"><form method="POST" action="{{ route('tasks.status', [$task->event, $task]) }}">@csrf @method('PATCH')<label for="personal-status-{{ $task->id }}">Status tugas</label><select id="personal-status-{{ $task->id }}" name="status">@foreach($labels as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach</select><p>Perubahan disimpan setelah konfirmasi.</p><x-button variant="secondary" size="sm">Simpan status</x-button></form></div></details>
                    @else<span class="tdb-pill tdb-pill-{{ $task->status }}"><span aria-hidden="true"></span>{{ $labels[$task->status] }}</span>@endif
                </td>
                <td class="tdb-event-cell" data-label="Event"><a class="ev-link" href="{{ route('workspace.overview', $task->event) }}">@display($clean($task->event->title))</a></td>
                <td class="tdb-deadline-cell" data-label="Tenggat"><time @class(['tdb-late' => $late]) @if($task->due_at) datetime="{{ $task->due_at->toIso8601String() }}" @endif>{{ $task->due_at?->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMM YYYY') ?? 'Tanpa tenggat' }}@if($task->due_at)<span>{{ $task->due_at->copy()->timezone('Asia/Makassar')->format('H:i') }} WITA</span>@endif @if($late)<strong>Lewat tenggat</strong>@endif</time></td>
                <td class="tdb-action-cell" data-label="Tindakan"><a class="tdb-text-link ev-link" href="{{ $detailUrl }}" aria-label="Detail @display($clean($task->title))">Detail <x-icon name="arrow" size="14" /></a>@if($canManage)<a class="tdb-text-link ev-link" href="{{ route('tasks.edit', [$task->event, $task]) }}">Edit / PIC</a>@endif</td>
            </tr>
        @endforeach
        </tbody></table>
    </div>
    @else
        <div class="tdb-empty">
            <x-icon name="task" size="22" />
            <h2>{{ $filtered || $filters['view'] !== 'all' ? 'Tidak ada tugas yang cocok.' : 'Belum ada tugas untuk Anda.' }}</h2>
        </div>
    @endif
    @if($tasks->hasPages())<div class="tdb-pagination">{{ $tasks->links() }}</div>@endif
</div>
@endsection
