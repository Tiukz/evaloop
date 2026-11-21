@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<section class="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
    <h1 class="text-2xl font-semibold">Buat akun</h1>
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-lg bg-ev-danger-soft p-3 text-sm text-ev-danger">Pendaftaran belum berhasil. Periksa isian Anda.</div>
    @endif
    <form action="{{ route('register.store') }}" method="POST" class="mt-6 space-y-5">
        @csrf
        <x-input name="name" label="Nama" autocomplete="name" required maxlength="150" />
        <x-input name="email" label="Email" type="email" autocomplete="email" required maxlength="190" />
        <x-input name="password" label="Password" type="password" autocomplete="new-password" required minlength="8" maxlength="128" />
        <x-input name="password_confirmation" label="Konfirmasi password" type="password" autocomplete="new-password" required maxlength="128" />
        <x-button class="w-full">Daftar</x-button>
    </form>
    <p class="mt-6 text-sm">Sudah punya akun? <a href="{{ route('login') }}" class="ev-link text-ev-primary underline">Masuk</a></p>
</section>
@endsection
