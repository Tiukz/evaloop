@extends('layouts.app')
@section('title', 'Tandai Event Selesai')
@section('breadcrumb', 'Event Workspace / Penyelesaian')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    <x-workspace-nav :event="$event" />
    <header class="page-header"><h1 class="page-title">Tandai event selesai</h1></header>
    <section class="operational-form max-w-2xl">
        <div class="form-section">
            <div class="mb-4"><x-status-badge :status="$event->status" /></div>
            <h2>@display($event->title)</h2>
            @if($event->canBeCompleted())
                <p class="mt-3 text-sm leading-6 text-slate-600">Event akan masuk riwayat di Event Radar. Rapat, tugas, dan evaluasi tetap tersedia di workspace.</p>
            @else
                <p class="mt-3 text-sm leading-6 text-slate-600">Hanya event yang sudah dipublikasikan yang dapat ditandai selesai.</p>
            @endif
        </div>
        @if($event->canBeCompleted())
            <form action="{{ route('workspace.complete', $event) }}" method="POST">
                @csrf
                <div class="form-actions">
                    <x-button loading-label="Menyimpan…">Konfirmasi selesai</x-button>
                    <a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali ke workspace</a>
                </div>
            </form>
        @else
            <div class="form-actions"><a class="ev-link" href="{{ route('workspace.overview', $event) }}">Kembali ke workspace</a></div>
        @endif
    </section>
</div>
@endsection
