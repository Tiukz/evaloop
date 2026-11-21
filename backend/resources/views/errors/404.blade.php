@extends('layouts.app')
@section('title', 'Halaman tidak ditemukan')
@section('content')
<section class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
    <p class="text-sm font-semibold text-ev-primary">404</p>
    <h1 class="mt-3 text-3xl font-semibold">Halaman tidak ditemukan</h1>
    <p class="my-5 leading-7 text-slate-600">Tautan mungkin tidak tepat atau data tidak tersedia pada konteks organisasi/event ini.</p>
    <div class="flex flex-wrap gap-3"><x-button :href="auth()->check() ? route('dashboard') : route('login')">{{ auth()->check() ? 'Kembali ke dashboard' : 'Masuk' }}</x-button><a class="ev-link self-center py-2 text-ev-primary underline" href="{{ route('events.index') }}">Event Radar</a></div>
</section>
@endsection
