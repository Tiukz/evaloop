<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@display(trim($__env->yieldContent('title', 'Evaloop'))) · Evaloop</title>
    @if(!app()->environment('testing'))
    <link rel="preload" href="{{ Vite::asset('resources/fonts/inter/inter-latin-variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/geist/geist-latin-variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    @endif
    @vite(['resources/css/app.css', 'resources/js/interactions.js'])
</head>
<body @class(['app-body', 'app-body-public' => !auth()->check()])>
    <div id="ev-page-progress" class="ev-page-progress" role="progressbar" aria-label="Memuat halaman" hidden></div>
    <a href="#main" class="app-skip-link">Lewati navigasi</a>
    <p id="ev-submit-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
    @auth<x-sidebar />@endauth
    <div class="app-stage">
        <x-navbar />
        <main id="main" class="app-content" tabindex="-1">
            @if(session('status'))<p role="status" class="ev-notice mb-6 rounded-lg border border-ev-success/20 bg-ev-success-soft p-4 text-sm text-ev-success">{{ session('status') }}</p>@endif
            @if($errors->any())
            <div id="form-errors" tabindex="-1" role="alert" class="ev-notice mb-6 rounded-lg border border-ev-danger/20 bg-ev-danger-soft p-4 text-sm text-ev-danger"><p class="font-semibold">Periksa kembali isian Anda.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
