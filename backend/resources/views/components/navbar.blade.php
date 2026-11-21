@auth
<header class="app-topbar">
    <nav aria-label="Konteks halaman" class="topbar-inner">
        <div class="topbar-breadcrumb"><x-icon name="home" size="16" /><span class="min-w-0 truncate text-slate-700">{{ trim($__env->yieldContent('breadcrumb', 'Ruang kerja')) }}</span></div>
        @if(!request()->routeIs('events.*', 'home'))<a class="topbar-discovery ev-link" href="{{ route('events.index') }}"><x-icon name="radar" size="16" /><span>Event Radar</span></a>@endif
    </nav>
</header>
@else
<header class="public-topbar"><nav aria-label="Navigasi utama" class="public-navigation">
    <a class="public-brand ev-link" href="{{ route('events.index') }}" aria-label="Evaloop — Event Radar">evaloop<span>.</span></a>
    <div class="public-explore"><a href="{{ route('events.index') }}" class="public-radar-link ev-link" @if(request()->routeIs('events.*', 'home')) aria-current="page" @endif><x-icon name="radar" size="17" /><span>Event Radar</span></a></div>
    <div class="public-auth"><a href="{{ route('login') }}" class="ev-nav-item" @if(request()->routeIs('login')) aria-current="page" @endif>Masuk</a><x-button :href="route('register')" size="sm">Daftar</x-button></div>
</nav></header>
@endauth
