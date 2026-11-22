<section aria-labelledby="ecc-work-heading">
    <div class="ecc-section-head">
        <h2 id="ecc-work-heading">{{ $archived ? 'Arsip tugas kepanitiaan' : ($canManage ? 'Prioritas pekerjaan' : 'Tugas aktif Anda') }}</h2>
        <a class="ecc-link ev-link" href="{{ route('workspace.tasks', $event) }}">Semua tugas</a>
    </div>
    @if($total && ($canManage || $archived))
        <div class="ecc-work-progress">
            <p>{{ $done }} dari {{ $total }} tugas selesai</p>
            <x-completion-meter :done="$done" :total="$total" :in-progress="$taskCounts['in_progress'] ?? 0" label="Progres tugas kepanitiaan" class="mt-2" />
        </div>
    @endif
    <div class="ecc-task-list">
        @forelse($overviewTasks as $task)
            <a class="ecc-task-row ev-link" href="{{ route('workspace.tasks', [$event, 'task' => $task->id]) }}#task-{{ $task->id }}">
                <span @class(['ecc-task-mark', 'ecc-task-done' => $task->status === 'done', 'ecc-task-progress' => $task->status === 'in_progress']) aria-hidden="true">@if($task->status === 'done')✓@endif</span>
                <div>
                    <h3>@display($task->title)</h3>
                    <p>
                        <span>{{ $taskLabels[$task->status] }}</span>
                        @if($task->assignee)
                            <span>@display($task->assignee->name)</span>
                        @elseif(!$archived)
                            <span>PIC belum ditetapkan</span>
                        @endif
                    </p>
                </div>
                @if(!$archived && $task->due_at)
                    @php($overdue = $task->status !== 'done' && $task->due_at->isPast())
                    <time @class(['ecc-overdue' => $overdue]) datetime="{{ $task->due_at->toIso8601String() }}">
                        @if($overdue)<strong>Lewat tenggat</strong>@endif
                        {{ $task->due_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMM YYYY') }}
                    </time>
                @endif
            </a>
        @empty
            <p class="ecc-quiet">{{ !$canManage && !$archived ? 'Tidak ada tugas aktif untuk Anda.' : ($total ? 'Tidak ada tugas aktif.' : 'Belum ada tugas.') }}</p>
        @endforelse
    </div>
    @if(!$archived)
        @can('manage', $event)
            <a class="ecc-link ev-link" href="{{ route('tasks.create', $event) }}">Bagikan tugas</a>
        @endcan
    @endif
</section>
