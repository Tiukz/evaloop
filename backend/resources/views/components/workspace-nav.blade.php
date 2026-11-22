@props(['event', 'active' => 'overview', 'showContextDetails' => true])
@php($workspaceTitle = preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $event->title))
<a class="section-link ev-link mb-1 gap-2" href="{{ route('my.events') }}"><x-icon name="arrow" size="14" class="rotate-180" />Kegiatan Saya</a>
<header class="workspace-module-context"><span class="workspace-module-home wrap-anywhere">{{ $workspaceTitle }}</span><div><x-status-badge :status="$event->status" />@if($showContextDetails)<span>Edisi {{ $event->starts_at->copy()->timezone('Asia/Makassar')->format('Y') }}</span>@endif</div></header>
<x-workspace-tabs :event="$event" :active="$active" />
@if($showContextDetails && in_array($event->status, ['completed', 'cancelled'], true))<p class="workspace-module-state" role="note">{{ $event->status === 'completed' ? 'Kegiatan selesai. Anda sedang meninjau catatan dan pembelajaran edisi ini.' : 'Kegiatan dibatalkan. Catatan yang tersimpan tetap dapat diakses sesuai kewenangan.' }}</p>@endif
