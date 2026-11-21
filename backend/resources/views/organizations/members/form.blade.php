@extends('layouts.app')
@section('title', $membership->exists ? 'Edit Keanggotaan' : 'Tambah Anggota')
@section('breadcrumb', 'Organisasi / Form keanggotaan')
@section('content')
<x-organization-nav :organization="$organization" active="members" />
<header class="page-header"><div><h2 class="page-title">{{ $membership->exists ? 'Edit Keanggotaan' : 'Tambah Anggota' }}</h2></div></header>
<div class="form-layout"><form action="{{ $membership->exists ? route('organizations.members.update', [$organization, $membership]) : route('organizations.members.store', $organization) }}" method="POST" class="operational-form">@csrf @if($membership->exists) @method('PATCH') @endif
<x-form-section title="Anggota & peran">
@if($membership->exists)<p class="text-sm font-semibold">@display($membership->user->name)</p>@else<x-input name="user_id" label="Pengguna terdaftar (ID akun)" type="number" min="1" required />@endif
@if($membership->exists && $membership->user_id === auth()->id())<p class="text-sm leading-6 text-slate-500">Peran sendiri tidak dapat diubah melalui form ini.</p>@else<x-select name="role" label="Peran" required>@foreach(['member' => 'Anggota', 'admin' => 'Admin organisasi'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $membership->role ?? 'member') === $value)>{{ $label }}</option>@endforeach</x-select>@endif
</x-form-section>
<x-form-section title="Status & masa keanggotaan" description="Status aktif tetap harus berada dalam rentang waktu yang berlaku."><x-select name="status" label="Status keanggotaan" required>@foreach(['active' => 'Aktif', 'inactive' => 'Tidak aktif'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $membership->status ?? 'active') === $value)>{{ $label }}</option>@endforeach</x-select><div class="form-field-grid"><x-input name="joined_at" label="Mulai keanggotaan (WITA)" type="datetime-local" :value="$membership->joined_at?->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" required /><x-input name="ended_at" label="Akhir keanggotaan (WITA, opsional)" type="datetime-local" :value="$membership->ended_at?->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" /></div></x-form-section>
<div class="form-actions"><x-button loading-label="Menyimpan…">Simpan</x-button><a class="ev-link" href="{{ route('organizations.members', $organization) }}">Kembali ke anggota organisasi</a></div></form><aside class="context-rail"><h2>Menjaga kesinambungan</h2><p>Organisasi tidak boleh kehilangan admin aktif terakhir tanpa pengganti.</p><p>Keanggotaan organisasi yang berakhir tidak otomatis menghapus keanggotaan event atau histori tugas.</p></aside></div>
@endsection
