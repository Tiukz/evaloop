@extends('layouts.app')
@section('title', 'Sumber Legacy')
@section('breadcrumb', 'Legacy Insights / Sumber evaluasi')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    <x-workspace-nav :event="$event" active="legacy" :show-context-details="false" />
    <div class="max-w-4xl">
        <header class="mb-4">
            <a class="section-link ev-link gap-2" href="{{ route('workspace.legacy', $event) }}">
                <x-icon name="arrow" size="14" class="rotate-180" /> Legacy Insights
            </a>
            <h1 class="page-title">Evaluasi dari @display($lesson->event->title)</h1>
        </header>
        <article class="legacy-source-document">
            <x-lesson-attribution :lesson="$lesson" full-time editorial class="mb-5" />
            <x-lesson-content :lesson="$lesson" editorial />
            <x-lesson-attachments :event="$event" :lesson="$lesson" image-previews class="mt-5" />
            @if($action)
                <x-legacy-action :event="$event" :task="$action" editorial />
            @elseif($lesson->event_id !== $event->id)
                <div class="mt-4 border-t border-ev-border pt-3">
                    <h2>Tindak lanjut</h2>
                    <p class="mt-1 text-sm text-ev-neutral-ink">Belum ada tugas tindak lanjut pada edisi ini.</p>
                    @if($canManage)
                        <x-button :href="route('legacy.action.create', [$event, $lesson])" class="mt-3">Buat Tindak Lanjut</x-button>
                    @endif
                </div>
            @endif
        </article>
    </div>
</div>
@endsection
