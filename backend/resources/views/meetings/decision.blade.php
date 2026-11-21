@extends('layouts.app')
@section('title', 'Edit Keputusan')
@section('breadcrumb', 'Meeting Hub / Edit keputusan')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    <x-workspace-nav :event="$event" active="meetings" :show-context-details="false" />
    <div class="min-w-0 max-w-2xl">
        <a class="section-link ev-link gap-2" href="{{ route('meetings.show', [$event, $meeting]) }}#decisions-heading">
            <x-icon name="arrow" size="15" class="rotate-180" /> Kembali ke rapat
        </a>
        <header class="mb-5">
            <h1 class="page-title">Edit keputusan</h1>
            <p class="mt-1 text-sm text-slate-600 wrap-anywhere">@display($meeting->title)</p>
        </header>
        <form method="POST" action="{{ route('decisions.update', [$event, $meeting, $decision]) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            @include('meetings.partials.decision-fields')
            <x-button loading-label="Menyimpan…">Simpan keputusan</x-button>
        </form>
    </div>
</div>
@endsection
