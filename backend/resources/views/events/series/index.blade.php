@extends('layouts.app')
@section('title', 'Event Series')
@section('breadcrumb', 'Organisasi / Event Series')
@section('content')
<x-organization-nav :organization="$organization" active="series" />
<header class="page-header"><div><h2 class="page-title">Event Series</h2></div><x-button :href="route('series.create', $organization)">Buat seri <x-icon name="book" size="16" /></x-button></header>
@if($series->isEmpty())<x-empty-state title="Belum ada Event Series" message="Seri menghubungkan beberapa edisi kegiatan tanpa menyalin data kepanitiaan." />@else
<ul class="entity-directory">@foreach($series as $item)<li class="entity-row"><div class="entity-identity"><span class="entity-monogram"><x-icon name="book" size="19" /></span><div class="min-w-0"><h2 class="text-base font-semibold"><a class="ev-link" href="{{ route('series.show', [$organization, $item]) }}">@display($item->name)</a></h2><p class="mt-2 text-xs text-slate-500">{{ $item->events_count }} edisi · @display($organization->name)</p></div></div><a class="section-link ev-link" href="{{ route('series.show', [$organization, $item]) }}">Telusuri edisi <x-icon name="arrow" size="15" /></a></li>@endforeach</ul><div class="mt-6">{{ $series->links() }}</div>@endif
@endsection
