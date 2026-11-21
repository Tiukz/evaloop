@extends('layouts.app')
@section('title', 'Kepanitiaan')
@section('breadcrumb', 'Event Workspace / Kepanitiaan')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
@php($canManage = auth()->user()->can('manage', $event))
<x-workspace-nav :event="$event" active="members" />
<header class="page-header"><div><h1 class="page-title">Kepanitiaan</h1></div>@if($canManage)<x-button :href="route('workspace.members.create', $event)">Tambah panitia <x-icon name="people" size="16" /></x-button>@endif</header>
<div class="reading-layout"><div class="min-w-0"><p class="mb-4 text-xs text-slate-500">{{ $memberships->total() }} anggota panitia</p>
@if($memberships->isEmpty())<x-empty-state title="Belum ada panitia" message="Kepanitiaan ditetapkan khusus untuk edisi event ini." />@else
<ul class="entity-directory">@foreach($memberships as $membership)<li class="entity-row"><div class="entity-identity"><span class="entity-monogram"><x-icon name="people" size="18" /></span><div class="min-w-0"><h2 class="text-sm font-semibold">@display($membership->user->name)</h2><p class="mt-1 text-xs text-slate-500">{{ $membership->role === 'coordinator' ? 'Ketua panitia' : 'Anggota panitia' }} · ID akun #{{ $membership->user_id }}</p></div></div>@if($canManage && $membership->role === 'member')<div class="entity-actions"><a class="section-link ev-link" href="{{ route('workspace.members.edit', [$event, $membership]) }}">Edit anggota</a><form action="{{ route('workspace.members.destroy', [$event, $membership]) }}" method="POST">@csrf @method('DELETE')<x-button variant="ghost" size="sm" class="text-ev-danger!">Keluarkan</x-button></form></div>@elseif($membership->role === 'coordinator')<span class="role-label">Ketua panitia</span>@endif</li>@endforeach</ul><div class="mt-6">{{ $memberships->links() }}</div>@endif
</div><aside class="context-rail">
@can('assignLeader', $event)<h2>Penunjukan ketua panitia</h2><p>Ketua hanya ditetapkan oleh admin organisasi. Ketua sebelumnya tetap menjadi anggota ketika diganti.</p><form action="{{ route('workspace.leader', $event) }}" method="POST" class="space-y-4">@csrf @method('PUT')<x-input name="user_id" label="Ketua panitia (ID akun terdaftar)" type="number" min="1" required /><x-button class="w-full">Tetapkan ketua</x-button></form>@else<h2>Peran dalam edisi ini</h2><p>Ketua mengelola koordinasi dan anggota panitia. Anggota dapat membaca workspace dan memperbarui progres tugas miliknya.</p>@endcan
<p>Anggota yang masih direferensikan tugas tidak dapat dikeluarkan sebelum PIC dialihkan secara eksplisit. Riwayat tugas tidak dihapus otomatis.</p></aside></div>
</div>
@endsection
