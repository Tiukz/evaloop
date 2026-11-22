@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Ruang kerja / Dashboard')
@section('content')
@include('dashboard.partials.command-center-styles')
@php
    $hasWorkspaceRole = $eventRoles->isNotEmpty() || $adminOrganizations->isNotEmpty();
    $clean = fn ($value) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $value ?? '');
    $total = $taskCounts->sum();
    $done = $taskCounts['done'] ?? 0;
    $remaining = $total - $done;
    $progress = $total ? round($done / $total * 100) : 0;
@endphp
<div class="personal-center">
    <header class="pc-header">
        <div class="pc-welcome">
            <p class="pc-date">{{ now()->timezone('Asia/Makassar')->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
            <h1>Selamat datang kembali.</h1>
            <div class="pc-roles" aria-label="Peran akun">
                @if(auth()->user()->is_platform_admin)<span>Admin platform</span>@endif
                @if($universities->isNotEmpty())<span>Admin universitas</span>@endif
                @if($adminOrganizations->isNotEmpty())<span>Admin organisasi</span>@endif
                @if($eventRoles->contains('coordinator'))<span>Ketua panitia</span>@elseif($eventRoles->contains('member'))<span>Anggota panitia</span>@endif
                @if(!$hasWorkspaceRole && $universities->isEmpty() && !auth()->user()->is_platform_admin)<span>Mahasiswa</span>@endif
                
            </div>
        </div>
        <div class="pc-primary">
            @if($adminOrganizations->isNotEmpty())<x-button :href="route('organization.events.create', $adminOrganizations->first())">Buat event <x-icon name="arrow" size="16" /></x-button>
            @elseif($universities->isNotEmpty())<x-button :href="route('university.organizations.create', $universities->first())">Buat organisasi</x-button>
            @elseif($eventRoles->contains('coordinator') && $activeEvents->contains(fn ($event) => ($eventMembershipRoles[$event->id] ?? null) === 'coordinator'))
                @php($leadingEvent = $activeEvents->first(fn ($event) => ($eventMembershipRoles[$event->id] ?? null) === 'coordinator'))
                <x-button :href="route('workspace.overview', $leadingEvent)">Buka workspace <x-icon name="arrow" size="16" /></x-button>
            @elseif($hasWorkspaceRole)<x-button :href="route('my.tasks')">Lihat tugas saya</x-button>
            @else<x-button :href="route('events.index')">Jelajahi Event Radar</x-button>@endif
        </div>
    </header>

    @if($universities->isNotEmpty())
    <section class="pc-administration" aria-labelledby="pc-admin-heading">
        <div><x-icon name="organization" size="22" /><h2 id="pc-admin-heading">Administrasi organisasi</h2></div>
        <div class="pc-admin-links">@foreach($universities as $university)<a href="{{ route('university.organizations.index', $university) }}" class="pc-admin-link ev-link"><span>@display($clean($university->name))<strong>Kelola organisasi universitas</strong></span><x-icon name="arrow" size="18" /></a>@endforeach</div>
    </section>
    @endif

    @if($hasWorkspaceRole)
    <div class="pc-columns">
        <div class="pc-main">
            <section class="pc-focus" aria-labelledby="pc-focus-heading">
                <div class="pc-section-heading"><div><h2 id="pc-focus-heading">Yang perlu dikerjakan</h2></div><a class="pc-text-link ev-link" href="{{ route('my.tasks') }}">Semua tugas <x-icon name="arrow" size="15" /></a></div>
                @if($priorityTasks->isNotEmpty())
                <div class="pc-task-list">@foreach($priorityTasks as $task)
                    @php($overdue = $task->due_at?->isPast())
                    <a @class(['pc-task', 'pc-task-overdue' => $overdue]) href="{{ route('workspace.tasks', [$task->event, 'task' => $task->id]) }}#task-{{ $task->id }}">
                        <span @class(['pc-task-symbol', 'pc-task-symbol-progress' => $task->status === 'in_progress']) aria-hidden="true">@if($task->status === 'in_progress')<span></span>@endif</span>
                        <div class="pc-task-content"><h3>@display($clean($task->title))</h3><p>@display($clean($task->event->title))</p></div>
                        <div class="pc-task-state"><span>{{ $task->status === 'in_progress' ? 'Sedang dikerjakan' : 'Belum dimulai' }}</span><time @if($task->due_at) datetime="{{ $task->due_at->toIso8601String() }}" @endif>@if($overdue)<strong>Lewat tenggat · </strong>@endif{{ $task->due_at?->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMM YYYY') ?? 'Tanpa tenggat' }}</time></div>
                        <x-icon name="arrow" size="16" class="pc-row-arrow" />
                    </a>
                @endforeach</div>
                @else<div class="pc-empty"><x-icon name="task" size="22" /><div><h3>Tidak ada tugas yang menunggu.</h3></div></div>@endif
            </section>

            <section class="pc-events" aria-labelledby="pc-events-heading">
                <div class="pc-section-heading"><div><h2 id="pc-events-heading">Event Anda</h2></div><a class="pc-text-link ev-link" href="{{ route('my.events') }}">Buka event Anda <x-icon name="arrow" size="15" /></a></div>
                <div class="pc-event-list">@forelse($activeEvents as $event)
                    @php($eventRole = $eventMembershipRoles[$event->id] ?? null)
                    <article class="pc-event">
                        <div class="pc-event-date text-ev-info" aria-label="{{ $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMMM YYYY') }}"><strong>{{ $event->starts_at->copy()->timezone('Asia/Makassar')->format('d') }}</strong><span>{{ $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('MMM YYYY') }}</span></div>
                        <div class="pc-event-body"><div class="pc-event-status"><x-status-badge :status="$event->status" /><span>{{ $adminOrganizations->contains('id', $event->organization_id) ? 'Admin organisasi' : ($eventRole === 'coordinator' ? 'Ketua panitia' : 'Anggota panitia') }}</span></div><h3><a class="ev-link" href="{{ route('workspace.overview', $event) }}">@display($clean($event->title))</a></h3><p>@display($clean($event->organization->name))</p></div>
                        <div class="pc-event-followup">@if($event->tasks_count)<span>{{ $event->done_tasks_count }} / {{ $event->tasks_count }} tugas selesai</span>@else<span>Belum ada tugas</span>@endif<a class="pc-text-link ev-link" href="{{ route('workspace.overview', $event) }}">Workspace</a></div>
                    </article>
                @empty<div class="pc-empty"><x-icon name="event" size="22" /><div><h3>Belum ada event aktif.</h3></div></div>@endforelse</div>
            </section>
        </div>
        <aside class="pc-aside" aria-label="Agenda dan progres pribadi">
            <section class="pc-agenda" aria-labelledby="pc-agenda-heading"><div class="pc-section-heading"><div><h2 id="pc-agenda-heading">Agenda berikutnya</h2></div></div>
                @forelse($upcomingMeetings->groupBy(fn ($meeting) => $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('Y-m-d')) as $date => $meetings)
                    <div class="pc-agenda-day"><h3>{{ $meetings->first()->scheduled_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('dddd, D MMM YYYY') }}</h3>@foreach($meetings as $meeting)<a class="pc-meeting" href="{{ route('meetings.show', [$meeting->event, $meeting]) }}"><time datetime="{{ $meeting->scheduled_at->toIso8601String() }}">{{ $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('H:i') }}<span>WITA</span></time><div><h4>@display($clean($meeting->title))</h4><p>@display($clean($meeting->event->title))</p></div></a>@endforeach</div>
                @empty<p class="pc-agenda-empty">Belum ada rapat mendatang.</p>@endforelse
            </section>
            <section class="pc-progress" aria-labelledby="pc-progress-heading"><div class="pc-section-heading"><div><h2 id="pc-progress-heading">Progres pribadi</h2></div></div>
                @if($total)<div class="pc-progress-summary"><strong>{{ $progress }}<span>%</span></strong><p>{{ $done }} dari {{ $total }} tugas selesai</p></div><x-completion-meter :done="$done" :total="$total" :in-progress="$taskCounts['in_progress'] ?? 0" label="Tugas pribadi selesai" /><dl class="pc-progress-details"><div><dt>Belum selesai</dt><dd>{{ $remaining }}</dd></div><div><dt>Sedang dikerjakan</dt><dd>{{ $taskCounts['in_progress'] ?? 0 }}</dd></div></dl>@else<p class="pc-agenda-empty">Belum ada tugas untuk dirangkum.</p>@endif
            </section>
            @if($adminOrganizations->isNotEmpty())<section class="pc-managed"><h2>Organisasi yang Anda kelola</h2>@foreach($adminOrganizations->take(3) as $organization)<a class="pc-org-link ev-link" href="{{ route('organizations.overview', $organization) }}"><x-icon name="organization" size="18" /><span>@display($clean($organization->name))</span><x-icon name="arrow" size="15" /></a>@endforeach<a class="pc-text-link ev-link" href="{{ route('my.organizations') }}">Semua organisasi</a></section>@endif
        </aside>
    </div>
    @else
    <section class="pc-start" aria-labelledby="pc-start-heading"><div class="pc-start-visual" aria-hidden="true"><x-icon :name="$universities->isNotEmpty() || auth()->user()->is_platform_admin ? 'organization' : 'radar'" size="36" /></div><div><h2 id="pc-start-heading">@if($universities->isNotEmpty() || auth()->user()->is_platform_admin)Ruang administrasi, akses yang terjaga.@else Kegiatan kampus, dalam jangkauan Anda.@endif</h2><p>@if($universities->isNotEmpty() || auth()->user()->is_platform_admin)Akses institusi tidak otomatis membuka rapat, tugas, dan evaluasi privat kepanitiaan. Pilih organisasi untuk melanjutkan administrasi yang tersedia.@else Temukan kegiatan lintas fakultas melalui Event Radar. Saat Anda bergabung dalam kepanitiaan, tugas dan agenda akan terhubung di ruang ini.@endif</p>@if($universities->isEmpty() && auth()->user()->is_platform_admin)<p>Bootstrap universitas dan admin pertamanya dilakukan melalui proses administratif tepercaya.</p>@endif</div></section>
    @endif
    @include('dashboard.partials.legacy-intro')
</div>
@endsection
