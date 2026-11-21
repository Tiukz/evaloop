@extends('layouts.app')
@section('title', $meeting->exists ? 'Edit Rapat' : 'Buat Rapat')
@section('breadcrumb', 'Meeting Hub / Form rapat')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
    <x-workspace-nav :event="$event" active="meetings" :show-context-details="false" />
    @php
        $edit = $meeting->exists;
        $returnUrl = $edit ? route('meetings.show', [$event, $meeting]) : route('workspace.meetings', $event);
    @endphp
    <div class="min-w-0 max-w-3xl">
        <a class="section-link ev-link gap-2" href="{{ $returnUrl }}">
            <x-icon name="arrow" size="15" class="rotate-180" /> {{ $edit ? 'Kembali ke rapat' : 'Daftar rapat' }}
        </a>
        <header class="mb-5">
            <h1 class="page-title">{{ $edit ? 'Edit catatan rapat' : 'Buat rapat' }}</h1>
            @if($edit)<p class="mt-1 text-sm text-slate-600 wrap-anywhere">@display($meeting->title)</p>@endif
        </header>
        <form method="POST" action="{{ $edit ? route('meetings.update', [$event, $meeting]) : route('meetings.store', $event) }}" class="space-y-5">
            @csrf
            @if($edit)
                @method('PATCH')
                <x-textarea name="notes" label="Catatan rapat" :value="$meeting->notes" maxlength="15000" rows="7"
                            class="min-h-44 max-h-[65vh] scroll-mt-6 [field-sizing:content] sm:min-h-52" />
                <details @if($errors->hasAny(['title', 'scheduled_at'])) open @endif>
                    <summary class="min-h-11 cursor-pointer rounded-md py-2 text-sm font-medium text-ev-primary">Judul dan jadwal</summary>
                    @include('meetings.partials.meeting-fields')
                </details>
            @else
                @include('meetings.partials.meeting-fields')
                <x-textarea name="notes" label="Catatan rapat (opsional)" :value="$meeting->notes" maxlength="15000" rows="4"
                            class="min-h-32 max-h-[65vh] [field-sizing:content]" />
            @endif
            <x-button loading-label="Menyimpan…">Simpan rapat</x-button>
        </form>
    </div>
</div>
@endsection
