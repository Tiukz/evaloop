@extends('layouts.app')
@section('title', 'Sesi formulir telah berakhir')
@section('content')
<section class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
    <p class="text-sm font-semibold text-ev-primary">419</p>
    <h1 class="mt-3 text-3xl font-semibold">Sesi formulir telah berakhir</h1>
    <p class="my-5 leading-7 text-slate-600">Muat kembali halaman melalui dashboard atau masuk kembali, lalu ulangi tindakan. Jangan kirim ulang formulir lama.</p>
    <div class="flex flex-wrap gap-3"><x-button :href="auth()->check() ? route('dashboard') : route('login')">{{ auth()->check() ? 'Kembali ke dashboard' : 'Masuk' }}</x-button><a class="ev-link self-center py-2 text-ev-primary underline" href="{{ route('events.index') }}">Event Radar</a></div>
</section>
@endsection
