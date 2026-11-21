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
            <h1 class="page-title">Sumber evaluasi</h1>
        </header>
        <article class="legacy-source-document">
            <header class="mb-5">
                <h2 class="wrap-anywhere">@display($lesson->event->title)</h2>
                <x-lesson-attribution :lesson="$lesson" full-time class="mt-1" />
            </header>
            <x-lesson-content :lesson="$lesson" />
            <x-lesson-attachments :event="$event" :lesson="$lesson" class="mt-5" />
            @if($action)
                <x-legacy-action :event="$event" :task="$action" />
            @elseif($canManage)
                <div class="mt-4 border-t border-ev-border pt-3">
                    <x-button size="sm" :href="route('legacy.action.create', [$event, $lesson])">Buat Tindak Lanjut</x-button>
                </div>
            @elseif($lesson->event_id !== $event->id)
                <p class="mt-4 border-t border-ev-border pt-3 text-sm text-ev-neutral-ink">Belum ada tindak lanjut.</p>
            @endif
        </article>
    </div>
</div>
@endsection
