@props(['event', 'task', 'editorial' => false])
@php
    [$label, $tone] = match ($task->status) {
        'in_progress' => ['Sedang dikerjakan', 'text-ev-info'],
        'done' => ['Selesai', 'text-ev-success'],
        default => ['Belum dimulai', 'text-ev-neutral-ink'],
    };
    $due = $task->due_at?->copy()->timezone('Asia/Makassar')->locale('id');
@endphp

<div @class(['mt-6' => $editorial, 'mt-4 border-t border-ev-border pt-3' => !$editorial])>
    <p @class(['text-base font-semibold text-ev-ink' => $editorial, 'text-xs font-medium text-ev-neutral-ink' => !$editorial])>{{ $editorial ? 'Tindak lanjut' : 'Tindak lanjut pada edisi ini' }}</p>
    <p @class(['mt-1 wrap-anywhere font-semibold text-ev-ink', 'text-[1.0625rem] sm:text-lg' => $editorial])>@display($task->title)</p>
    <div @class(['flex flex-col items-start gap-x-4 sm:flex-wrap', 'gap-y-2' => $editorial, 'sm:flex-row sm:items-center' => !$editorial])>
        <div @class(['mt-1 flex flex-wrap gap-x-3 gap-y-1 leading-5 text-ev-neutral-ink', 'text-sm' => $editorial, 'text-xs sm:mt-0' => !$editorial])>
            <span class="{{ $tone }} font-medium">{{ $label }}</span>
            @if($due)
                <span>Tenggat <time datetime="{{ $due->toIso8601String() }}">{{ $due->isoFormat('D MMM YYYY · HH:mm') }} WITA</time></span>
            @else
                <span>Tanpa tenggat</span>
            @endif
        </div>
        @if($editorial)
            <x-button variant="secondary" :href="route('workspace.tasks', [$event, 'task' => $task->id]).'#task-'.$task->id">
                Lihat tugas
            </x-button>
        @else
            <a class="ev-link inline-flex min-h-11 items-center gap-1.5 rounded-sm text-sm font-medium text-ev-primary hover:underline"
               href="{{ route('workspace.tasks', [$event, 'task' => $task->id]) }}#task-{{ $task->id }}">Lihat tindak lanjut <x-icon name="arrow" size="14" /></a>
        @endif
    </div>
</div>
