@extends('layouts.app')
@section('title', 'Buat Event Series')
@section('breadcrumb', 'Event Series / Buat seri')
@section('content')
<x-organization-nav :organization="$organization" active="series" />
<header class="page-header"><div><h2 class="page-title">Buat Event Series</h2></div></header>
<div class="form-layout"><form action="{{ route('series.store', $organization) }}" method="POST" class="operational-form">@csrf<x-form-section title="Identitas seri"><x-input name="name" label="Nama seri kegiatan" required maxlength="180" /></x-form-section><div class="form-actions"><x-button loading-label="Menyimpan…">Simpan</x-button><a class="ev-link" href="{{ route('series.index', $organization) }}">Kembali ke Event Series</a></div></form><aside class="context-rail"><h2>Satu seri, banyak edisi</h2><p>Seri dimiliki oleh @display($organization->name). Memilih seri pada event tidak menyalin panitia, rapat, atau tugas.</p></aside></div>
@endsection
