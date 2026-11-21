@extends('layouts.app')
@section('title', 'Organisasi Saya')
@section('breadcrumb', 'Ruang kerja / Organisasi Saya')
@section('content')
<header class="page-header">
    <div>
        <h1 class="page-title">Organisasi Saya</h1>
    </div>
</header>

@foreach($universities as $university)
    <div class="mb-6 flex max-w-5xl flex-wrap items-center justify-between gap-3 border-y border-ev-border py-4">
        <p class="text-sm text-slate-600 wrap-anywhere">@display($university->name)</p>
        <a href="{{ route('university.organizations.index', $university) }}" class="section-link ev-link">Administrasi organisasi <x-icon name="arrow" size="15" /></a>
    </div>
@endforeach

@if($organizations->isEmpty())
    <x-empty-state title="Belum ada keanggotaan organisasi yang berlaku" message="Keanggotaan diberikan oleh admin berwenang dan mengikuti masa berlaku organisasi." />
@else
    <div class="@container max-w-5xl">
        <ul aria-label="Portofolio organisasi" class="grid grid-cols-1 gap-5 @min-[39rem]:grid-cols-2">
            @foreach($organizations as $organization)
                @php
                    [$scope, $scopeName] = match (true) {
                        $organization->study_program_id !== null => ['Program studi', $organization->studyProgram?->name],
                        $organization->faculty_id !== null => ['Fakultas', $organization->faculty?->name],
                        default => ['Universitas', $organization->university?->name],
                    };
                    $role = match ($organization->memberships->first()?->role) {
                        'admin' => 'Admin organisasi',
                        'member' => 'Anggota',
                        default => null,
                    };
                    $monogram = Str::of($organization->name)->replaceMatches('/^\[(?:SIMULASI|TECFEST)\]/iu', '')
                        ->matchAll('/[\p{L}\p{N}]+/u')->take(3)
                        ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('');
                @endphp
                <li class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-ev-border bg-white transition-colors duration-150 hover:border-ev-primary focus-within:border-ev-primary motion-reduce:transition-none">
                    <div @class([
                        'flex flex-col items-start justify-between gap-4 border-b border-ev-border px-[clamp(1rem,3vw,1.75rem)] py-5',
                        'bg-ev-aqua' => $loop->even,
                        'bg-ev-mist' => $loop->odd,
                    ])>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-ev-primary">{{ $scope }}</span>
                        @if($organization->logo_path)
                            <img src="{{ route('organizations.logo.show', $organization) }}" alt="" width="80" height="80"
                                class="size-[clamp(3rem,7vw,5rem)] rounded-xl border border-ev-border bg-white object-contain p-1.5">
                        @else
                            <span aria-hidden="true" class="font-artwork text-[clamp(3rem,7vw,5rem)] leading-none font-extrabold tracking-[-0.07em] text-ev-primary">{{ $monogram ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col gap-2 px-[clamp(1rem,3vw,1.75rem)] py-5">
                        <h2 id="organization-{{ $organization->id }}" class="text-xl leading-snug font-semibold tracking-tight wrap-anywhere">@display($organization->name)</h2>
                        @if($scopeName)
                            <p class="text-sm text-slate-600 wrap-anywhere">{{ $scopeName }}</p>
                        @endif
                        @if($role)
                            <p class="mt-1 text-sm font-semibold text-ev-primary">{{ $role }}</p>
                        @endif
                    </div>
                    <footer class="border-t border-ev-border px-[clamp(1rem,3vw,1.75rem)] py-2">
                        <a class="section-link ev-link gap-2 rounded-sm" href="{{ route('organizations.overview', $organization) }}"
                           aria-labelledby="open-organization-{{ $organization->id }} organization-{{ $organization->id }}">
                            <span id="open-organization-{{ $organization->id }}">Buka Organisasi</span>
                            <span class="motion-safe:transition-transform motion-safe:duration-150 motion-safe:group-hover:translate-x-[3px]"><x-icon name="arrow" size="16" /></span>
                        </a>
                    </footer>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $organizations->links() }}</div>
    </div>
@endif
@endsection
