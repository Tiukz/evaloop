@props(['organization', 'active' => 'overview', 'membership' => null, 'showCreateEvent' => false, 'showLogoControl' => false])
@once
    @include('organizations.partials.hub-styles')
@endonce
@php
    $canManageOrganization = auth()->user()->can('manage', $organization);
    $scope = $organization->study_program_id ? 'Program studi' : ($organization->faculty_id ? 'Fakultas' : 'Universitas');
    $plainName = preg_replace('/\[(?:SIMULASI|TECFEST)\]\s*/iu', '', $organization->name);
    $monogram = Str::of($plainName)->matchAll('/[\p{L}\p{N}]+/u')->take(3)
        ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('');
    $tabs = [
        'overview' => ['Ringkasan', 'home', 'organizations.overview'],
        'members' => ['Anggota', 'people', 'organizations.members'],
        'events' => ['Event organisasi', 'event', 'organizations.events'],
        'series' => ['Event Series', 'book', 'series.index'],
    ];
@endphp
<div class="organization-hub">
    <a class="oh-back ev-link" href="{{ route('my.organizations') }}"><x-icon name="arrow" size="14" class="rotate-180" />Organisasi Saya</a>
    <header class="oh-masthead">
        @if($organization->logo_path)
            <span class="oh-monogram overflow-hidden border border-ev-border bg-white!" aria-hidden="true">
                <img src="{{ route('organizations.logo.show', $organization) }}" alt="" class="size-full object-contain p-1">
            </span>
        @else
            <span class="oh-monogram" aria-hidden="true">{{ $monogram ?: '—' }}</span>
        @endif
        <div class="oh-identity">
            <div class="oh-context">
                <span>Organisasi {{ Str::lower($scope) }}</span>
                @if($membership || $canManageOrganization)
                    <span class="oh-role">{{ $canManageOrganization ? 'Admin organisasi' : 'Anggota organisasi' }}</span>
                @endif
            </div>
            <h1>@display($organization->name)</h1>
            <p>@display($organization->university->name)</p>
            <div class="oh-affiliation">
                @if($organization->faculty)<span>@display($organization->faculty->name)</span>@endif
                @if($organization->studyProgram)<span>@display($organization->studyProgram->name)</span>@endif
            </div>
        </div>
        @if($canManageOrganization && ($showCreateEvent || $showLogoControl))
            <div class="oh-masthead-actions flex min-w-0 flex-wrap items-start gap-2">
                @if($showCreateEvent)
                    <x-button :href="route('organization.events.create', $organization)">
                        <span class="inline-flex items-center gap-2"><x-icon name="event" size="16" />Buat event</span>
                    </x-button>
                @endif
                @if($showLogoControl)
                    <details class="min-w-0 max-w-full open:basis-full" @if($errors->has('logo')) open @endif>
                        <summary class="ev-button ev-button-secondary cursor-pointer">{{ $organization->logo_path ? 'Ganti logo' : 'Unggah logo organisasi' }}</summary>
                        <form method="POST" action="{{ route('organizations.logo.update', $organization) }}" enctype="multipart/form-data" class="mt-3 w-full min-w-0 max-w-64 space-y-3">
                            @csrf
                            @method('PUT')
                            <x-workspace-image-input name="logo" label="Pilih gambar logo" description="Logo tampil di sini dan pada detail event publik." required />
                            <x-button loading-label="Menyimpan…">Simpan logo</x-button>
                        </form>
                    </details>
                @endif
            </div>
        @endif
    </header>
    <nav class="oh-navigation" aria-label="Organisasi">
        @foreach($tabs as $key => [$label, $icon, $route])
            @if($key === 'overview' || $canManageOrganization)
                <a href="{{ route($route, $organization) }}" @if($active === $key) aria-current="page" @endif>
                    <x-icon :name="$icon" size="16" />{{ $label }}
                </a>
            @endif
        @endforeach
    </nav>
</div>
