@php
    $adminUniversities = auth()->user()->universityMemberships()->active()->where('role', 'admin')->with('university')->get()->pluck('university');
    $groups = [
        'Navigasi utama' => [
            ['dashboard', 'Dashboard', 'home', ['dashboard']],
            ['events.index', 'Event Radar', 'radar', ['events.*', 'home']],
            ['my.events', 'Kegiatan Saya', 'event', ['my.events', 'workspace.*', 'meetings.*', 'decisions.*', 'tasks.*', 'legacy.*', 'evaluations.*']],
            ['my.tasks', 'Tugas Saya', 'task', ['my.tasks']],
        ],
        'Pengelolaan' => [
            ['my.organizations', 'Organisasi Saya', 'organization', ['my.organizations', 'organizations.*', 'series.*', 'organization.events.*']],
        ],
    ];
@endphp
<aside class="app-sidebar" aria-label="Navigasi aplikasi">
    <button type="button" class="sidebar-mobile-toggle" data-sidebar-open aria-controls="mobile-sidebar" aria-expanded="false" hidden>
        <x-icon name="menu" size="20" /><span class="brand-wordmark">evaloop<span class="brand-period">.</span></span><span class="sr-only">Buka navigasi</span>
    </button>
    <details class="shell-navigation" data-shell-navigation>
        <summary class="mobile-navigation-trigger"><span class="brand-wordmark">evaloop<span class="brand-period">.</span></span><span class="flex items-center gap-2 text-sm">Menu <x-icon name="chevron" size="16" /></span></summary>
        <div class="sidebar-body" id="sidebar-content">
            <div class="sidebar-heading">
                <a href="{{ route('dashboard') }}" class="sidebar-brand"><span class="brand-wordmark">evaloop<span class="brand-period">.</span></span></a>
                <button type="button" class="sidebar-icon-button" data-sidebar-toggle aria-controls="sidebar-content" aria-expanded="true" aria-label="Ciutkan sidebar" hidden><x-icon name="sidebar" size="19" /></button>
                <button type="button" class="sidebar-icon-button" data-sidebar-close aria-label="Tutup navigasi" hidden><x-icon name="close" size="20" /></button>
            </div>
            <nav aria-label="Navigasi utama" class="sidebar-groups">
                @foreach($groups as $heading => $items)
                    <section class="sidebar-group"><h2>{{ $heading }}</h2>
                        @foreach($items as [$name, $label, $icon, $patterns])
                        <a class="sidebar-link" data-sidebar-label="{{ $label }}" aria-label="{{ $label }}" href="{{ route($name) }}" @if(request()->routeIs(...$patterns)) aria-current="page" @endif><x-icon :name="$icon" size="17" /><span>{{ $label }}</span></a>
                        @endforeach
                    </section>
                @endforeach
                @if($adminUniversities->isNotEmpty())
                <section class="sidebar-group"><h2>Administrasi</h2>@foreach($adminUniversities as $university)<a class="sidebar-link" data-sidebar-label="Organisasi universitas" aria-label="Organisasi universitas" href="{{ route('university.organizations.index', $university) }}" @if(request()->routeIs('university.organizations.*')) aria-current="page" @endif><x-icon name="organization" size="17" /><span>Organisasi universitas</span></a>@endforeach</section>
                @endif
            </nav>
            <div class="sidebar-foot"><span class="sidebar-pilot">MVP v1.1</span><x-account-menu /></div>
        </div>
    </details>
    <dialog id="mobile-sidebar" class="sidebar-drawer" aria-label="Navigasi aplikasi"></dialog>
    <span class="sidebar-tooltip" data-sidebar-tooltip aria-hidden="true" hidden></span>
</aside>
