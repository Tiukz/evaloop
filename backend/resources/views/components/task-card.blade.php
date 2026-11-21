@props(['task', 'canManage', 'showEvent' => false])
@php
    $labels = ['todo' => 'Belum dimulai', 'in_progress' => 'Sedang dikerjakan', 'done' => 'Selesai'];
    $late = $task->status !== 'done' && $task->due_at?->isPast();
@endphp
<article id="task-{{ $task->id }}" class="task-issue">
    <div class="task-issue-main"><span @class(['issue-status-mark', 'issue-status-mark-progress' => $task->status === 'in_progress', 'issue-status-mark-done' => $task->status === 'done']) aria-hidden="true"></span><div class="min-w-0 flex-1"><div class="mb-1 flex flex-wrap items-center gap-2"><span class="text-xs tabular-nums text-ev-neutral-ink">Tugas #{{ $task->id }}</span><x-status-badge :status="$task->status" />@if($late)<span class="attention-label">Lewat tenggat</span>@endif</div><h3 class="text-sm font-semibold leading-6">@display($task->title)</h3>
    <div class="issue-metadata">@if($showEvent)<a class="ev-link" href="{{ route('workspace.tasks', [$task->event, 'task' => $task->id]) }}#task-{{ $task->id }}">@display($task->event->title)</a>@endif<span>PIC: @display($task->assignee?->name ?? 'Belum ditetapkan')</span><time @if($task->due_at) datetime="{{ $task->due_at->toIso8601String() }}" @endif>Tenggat: {{ $task->due_at?->timezone('Asia/Makassar')->format('d M Y H:i') ?? 'Belum ditentukan' }}{{ $task->due_at ? ' WITA' : '' }}</time></div>
    @if($task->source_lesson_id)
        @if($task->sourceLesson && ($task->sourceLesson->event_id === $task->event_id || $task->sourceLesson->isInheritedBy($task->event)))<a class="issue-source ev-link" href="{{ route('legacy.source', [$task->event, $task->sourceLesson]) }}"><x-icon name="book" size="13" /> Sumber legacy: @display($task->sourceLesson->event->title)</a>@else<p class="mt-2 text-xs text-slate-500">Sumber legacy tersimpan; akses sumber tidak tersedia pada konteks event ini.</p>@endif
    @endif
    </div></div>
    <div class="task-issue-actions">
        @if($canManage || $task->assignee_user_id === auth()->id())
        <form method="POST" action="{{ route('tasks.status', [$task->event, $task]) }}" class="issue-status-form">@csrf @method('PATCH')<x-select name="status" :id="'task-'.$task->id.'-status'" label="Ubah progres">@foreach($labels as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach</x-select><x-button variant="secondary" size="sm">Perbarui</x-button></form>
        @endif
        @if($canManage)<a class="ev-link mt-2 inline-flex min-h-8 items-center text-xs text-ev-primary" href="{{ route('tasks.edit', [$task->event, $task]) }}">Edit tugas / PIC</a>@endif
    </div>
</article>
