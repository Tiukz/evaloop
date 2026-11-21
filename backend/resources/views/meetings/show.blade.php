@extends('layouts.app')
@section('title', $meeting->title)
@section('breadcrumb', 'Meeting Hub / Catatan rapat')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    @php
        $canManage = auth()->user()->can('manage', $event);
        $lastDecisionWithTasks = $meeting->decisions->last(fn ($decision) => $decision->tasks->isNotEmpty());
    @endphp
    <x-workspace-nav :event="$event" active="meetings" :show-context-details="false" />

    <div class="min-w-0 max-w-4xl">
        <header class="mb-6">
            <a class="section-link ev-link gap-2" href="{{ route('workspace.meetings', $event) }}">
                <x-icon name="arrow" size="15" class="rotate-180" /> Kembali ke daftar rapat
            </a>
            <h1 class="page-title wrap-anywhere">@display($meeting->title)</h1>
            <time class="mt-1 block text-sm text-ev-neutral-ink" datetime="{{ $meeting->scheduled_at->toIso8601String() }}">{{ $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('dddd, D MMMM YYYY · HH:mm') }} WITA</time>
        </header>

        <section id="catatan-rapat" class="meeting-notes rounded-xl border border-ev-border bg-ev-surface p-[clamp(1rem,3cqi,1.75rem)]" aria-labelledby="meeting-notes-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="meeting-notes-heading">Catatan rapat</h2>
                @if($canManage)
                    <x-button variant="secondary" :href="route('meetings.edit', [$event, $meeting]).'#notes'">Edit catatan</x-button>
                @endif
            </div>
            @if(filled($meeting->notes))
                <div class="mt-3 max-w-[72ch] wrap-anywhere whitespace-pre-wrap text-base leading-7 text-ev-ink">@display($meeting->notes)</div>
            @else
                <p class="mt-2 text-sm text-ev-neutral-ink">Belum ada catatan.</p>
            @endif
            @if(Str::length($meeting->notes ?? '') > 1200)
                <a class="section-link ev-link mt-3" href="#decisions-heading">Ke keputusan rapat</a>
            @endif
        </section>

        <section class="@container mt-6 rounded-xl border border-ev-border bg-ev-surface px-[clamp(1rem,3cqi,1.75rem)] py-4" aria-labelledby="decisions-heading">
            <div class="grid items-start gap-y-2 @min-[32rem]:grid-cols-[minmax(0,1fr)_auto]">
                <h2 id="decisions-heading" class="relative z-10 flex min-h-11 scroll-mt-6 items-center @min-[32rem]:col-start-1 @min-[32rem]:row-start-1" tabindex="-1">Keputusan rapat</h2>
                @if($canManage)
                    <details id="decision-form" class="@min-[32rem]:col-span-2 @min-[32rem]:col-start-1 @min-[32rem]:row-start-1"
                             @if($errors->hasAny(['summary', 'rationale']) || old('summary') !== null || old('rationale') !== null) open @endif>
                        <summary class="ev-button ev-button-primary flex w-fit cursor-pointer list-none items-center gap-2 [&::-webkit-details-marker]:hidden @min-[32rem]:ms-auto">
                            <span aria-hidden="true" class="text-lg leading-none">+</span> Tambah keputusan
                        </summary>
                        <form method="POST" action="{{ route('decisions.store', [$event, $meeting]) }}" class="mt-4 rounded-lg bg-ev-ice p-4">
                            @csrf
                            @include('meetings.partials.decision-fields')
                            <div class="mt-4">
                                <x-button loading-label="Menyimpan…">Simpan keputusan</x-button>
                            </div>
                        </form>
                    </details>
                @endif
            </div>

            @if($meeting->decisions->isNotEmpty())
                <ol role="list" class="mt-2 divide-y divide-ev-border">
                    @foreach($meeting->decisions as $decision)
                        <li class="py-5">
                            <article>
                                <h3 class="max-w-[72ch] wrap-anywhere whitespace-pre-wrap">@display($decision->summary)</h3>
                                @if(filled($decision->rationale))
                                    <p class="mt-2 max-w-[72ch] wrap-anywhere whitespace-pre-wrap text-sm leading-6 text-ev-neutral-ink">@display($decision->rationale)</p>
                                @endif

                                @if($decision->tasks->isNotEmpty())
                                    <div class="mt-4">
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                                            <h4 id="related-tasks-{{ $decision->id }}" class="text-sm font-semibold text-ev-info">Tugas terkait</h4>
                                            @if($decision === $lastDecisionWithTasks)
                                                <a class="section-link ev-link" href="{{ route('workspace.tasks', $event) }}">Lihat tugas & progres</a>
                                            @endif
                                        </div>
                                        <ul role="list" class="space-y-1.5" aria-labelledby="related-tasks-{{ $decision->id }}">
                                            @foreach($decision->tasks as $task)
                                                <li>
                                                    <a class="ev-link flex min-h-11 w-full items-center justify-between gap-3 rounded-lg bg-ev-ice px-3 py-2 text-sm font-medium text-ev-ink hover:bg-ev-mist"
                                                       href="{{ route('workspace.tasks', [$event, 'task' => $task->id]) }}#task-{{ $task->id }}">
                                                        <span class="min-w-0 wrap-anywhere">@display($task->title)</span>
                                                        <x-icon name="arrow" size="15" class="text-ev-info" />
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($canManage)
                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <x-button variant="secondary" size="sm" :href="route('tasks.create', [$event, 'decision' => $decision->id])">Buat tugas tindak lanjut</x-button>
                                        <x-button variant="ghost" size="sm" class="text-ev-neutral-ink" :href="route('decisions.edit', [$event, $meeting, $decision])">Edit keputusan</x-button>
                                    </div>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="py-4 text-sm text-ev-neutral-ink">Belum ada keputusan.</p>
            @endif

            @if(!$lastDecisionWithTasks)
                <footer class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    @if($canManage && $meeting->decisions->isEmpty())
                        <x-button variant="secondary" size="sm" :href="route('tasks.create', $event)">Buat tugas tindak lanjut</x-button>
                    @endif
                    <a class="section-link ev-link" href="{{ route('workspace.tasks', $event) }}">Lihat tugas & progres</a>
                </footer>
            @endif
        </section>
    </div>
</div>
@endsection
