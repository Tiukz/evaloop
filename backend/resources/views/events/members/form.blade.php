@extends('layouts.app')
@section('title', $membership->exists ? 'Edit Anggota Panitia' : 'Tambah Panitia')
@section('breadcrumb', 'Kepanitiaan / Form anggota')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module">
<x-workspace-nav :event="$event" active="members" />
<header class="page-header"><div><h1 class="page-title">{{ $membership->exists ? 'Edit Anggota Panitia' : 'Tambah Panitia' }}</h1></div></header>
<div class="form-layout"><form action="{{ $membership->exists ? route('workspace.members.update', [$event, $membership]) : route('workspace.members.store', $event) }}" method="POST" class="operational-form">@csrf @if($membership->exists) @method('PATCH') @endif
<x-form-section title="Anggota kepanitiaan">@if($membership->exists)<p class="text-sm font-semibold">@display($membership->user->name)</p>@else<x-input name="user_id" label="Pengguna terdaftar (ID akun)" type="number" min="1" required />@endif<x-select name="role" label="Peran"><option value="member">Anggota panitia</option></x-select></x-form-section>
<div class="form-actions"><x-button loading-label="Menyimpan…">Simpan</x-button><a class="ev-link" href="{{ route('workspace.members', $event) }}">Kembali ke kepanitiaan</a></div></form><aside class="context-rail"><h2>Pemisahan kewenangan</h2><p>Ketua hanya ditunjuk oleh admin organisasi melalui aksi terpisah.</p><p>Gunakan akun pengguna yang sudah terdaftar. Kepanitiaan edisi lain tidak disalin secara otomatis.</p></aside></div>
</div>
@endsection
