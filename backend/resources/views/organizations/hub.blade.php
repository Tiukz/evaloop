@extends('layouts.app')
@section('title', $organization->name)
@section('breadcrumb', 'Organisasi / Ringkasan')
@section('content')
<x-organization-nav :organization="$organization" :membership="$membership" :show-create-event="true" show-logo-control />
<div class="organization-hub">
    <div class="oh-layout">
        <div class="oh-primary">
            <section aria-labelledby="oh-events-heading"><div class="oh-section-heading"><div><h2 id="oh-events-heading">Kegiatan organisasi</h2></div></div>
                <div class="oh-event-list">@forelse($hubEvents as $event)
                    @php($canOpenWorkspace = $canManage || $event->memberships->isNotEmpty())
                    <article class="oh-event"><time class="oh-date" datetime="{{ $event->starts_at->toIso8601String() }}"><strong>{{ $event->starts_at->copy()->timezone('Asia/Makassar')->format('d') }}</strong><span>{{ $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('MMM YYYY') }}</span></time><div class="oh-event-info"><div class="oh-event-status"><x-status-badge :status="$event->status" />@if($event->status === 'draft')<span>Belum tampil di Event Radar</span>@endif</div><h3><a class="ev-link" href="{{ $canOpenWorkspace ? route('workspace.overview', $event) : route('events.show', $event->id) }}">@display($event->title)</a></h3><p><x-icon name="location" size="14" />@display($event->venue ?: 'Lokasi belum ditentukan')</p></div><a class="oh-event-action ev-link" href="{{ $canOpenWorkspace ? route('workspace.overview', $event) : route('events.show', $event->id) }}" aria-label="{{ $canOpenWorkspace ? 'Buka workspace' : 'Lihat event publik' }} {{ preg_replace('/\[(?:SIMULASI|TECFEST)\]\s*/iu', '', $event->title) }}">
                        @if($canOpenWorkspace)
                            Workspace
                        @else
                            Detail publik <x-icon name="arrow" size="15" />
                        @endif
                    </a></article>
                @empty<div class="oh-empty"><x-icon name="event" size="22" /><div><h3>Belum ada kegiatan yang dapat ditampilkan.</h3><p>{{ $canManage ? 'Gunakan Buat event untuk menyiapkan draft kegiatan pertama.' : 'Kegiatan akan muncul setelah dipublikasikan atau saat Anda bergabung dalam kepanitiaannya.' }}</p></div></div>@endforelse</div>
                @if($hubEvents->count() === 5)<p class="oh-note">Menampilkan lima kegiatan. {{ $canManage ? 'Daftar lengkap tersedia di tab Event organisasi.' : 'Gunakan Event Saya untuk workspace lainnya.' }}</p>@endif
            </section>
            <section class="oh-series-section" aria-labelledby="oh-series-heading"><div class="oh-section-heading"><div><h2 id="oh-series-heading">Kegiatan yang berlanjut</h2></div></div>
                @forelse($hubSeries as $series)<article class="oh-series"><div class="oh-series-title"><x-icon name="book" size="18" /><div><h3>@if($canManage)<a class="ev-link" href="{{ route('series.show', [$organization, $series]) }}">@display($series->name)</a>@else @display($series->name) @endif</h3><p>{{ $series->events_count }} edisi{{ $canManage ? ' tercatat' : ' dapat diakses' }}{{ $series->events_count > 2 ? ' · dua edisi terbaru ditampilkan' : '' }}</p></div></div><ol class="oh-editions">@forelse($series->events->sortBy('starts_at') as $edition)<li><span>{{ $edition->starts_at->copy()->timezone('Asia/Makassar')->format('Y') }}</span><a class="ev-link" href="{{ route('workspace.overview', $edition) }}">@display($edition->title)<x-icon name="arrow" size="14" /></a><x-status-badge :status="$edition->status" /></li>@empty<li><p>Belum ada edisi pada seri ini.</p></li>@endforelse</ol></article>@empty<div class="oh-empty"><x-icon name="book" size="22" /><div><h3>Belum ada rangkaian edisi yang tersedia.</h3><p>{{ $canManage ? 'Seri kegiatan yang dibuat akan menghubungkan penyelenggaraan antartahun.' : 'Seri akan terlihat saat ada edisi dengan workspace yang dapat Anda akses.' }}</p></div></div>@endforelse
            </section>
        </div>
        <aside class="oh-secondary" aria-label="Keanggotaan dan konteks organisasi">
            @if($canManage)<section class="oh-members"><div class="oh-section-heading"><h2>Keanggotaan</h2></div><p class="oh-members-total"><strong>{{ $memberCounts->sum() }}</strong> anggota berlaku</p><dl><div><dt>Admin organisasi</dt><dd>{{ $memberCounts['admin'] ?? 0 }}</dd></div><div><dt>Anggota</dt><dd>{{ $memberCounts['member'] ?? 0 }}</dd></div><div><dt>Tidak aktif / di luar masa berlaku</dt><dd>{{ $membershipTotal - $memberCounts->sum() }}</dd></div></dl><p class="oh-note">Status dan masa keanggotaan menentukan akses organisasi.</p></section>
            @else<section class="oh-members"><h2>Keanggotaan Anda</h2><p class="oh-member-role">{{ $membership?->role === 'admin' ? 'Admin organisasi' : 'Anggota organisasi' }}</p><p class="oh-note">Aktif sejak {{ $membership?->joined_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMM YYYY') }}@if($membership?->ended_at)<br>Berlaku sampai {{ $membership->ended_at->copy()->timezone('Asia/Makassar')->locale('id')->isoFormat('D MMM YYYY') }}@endif</p><p class="oh-note">Keanggotaan organisasi tidak otomatis memberikan akses ke semua kepanitiaan.</p></section>@endif

        </aside>
    </div>
</div>
@endsection
