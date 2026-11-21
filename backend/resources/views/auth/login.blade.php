@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<section class="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
    <h1 class="text-2xl font-semibold">Masuk ke Evaloop</h1>
    <form action="{{ route('login.store') }}" method="POST" class="mt-6 space-y-5">
        @csrf
        <x-input name="email" label="Email" type="email" autocomplete="email" required maxlength="190" />
        <x-input name="password" label="Password" type="password" autocomplete="current-password" required maxlength="128" />
        <x-button class="w-full">Masuk</x-button>
    </form>
    <p class="mt-6 text-sm">Belum punya akun? <a href="{{ route('register') }}" class="ev-link text-ev-primary underline">Daftar</a></p>
</section>
@endsection
