@extends('layouts.app')
@section('title', 'Legacy Insights')
@section('breadcrumb', 'Event Workspace / Legacy Insights')
@section('content')
@include('events.partials.workspace-module-styles')
<div class="workspace-module legacy-library">
    <x-workspace-nav :event="$event" active="legacy" :show-context-details="false" />

    <header class="mb-9 grid items-center gap-x-10 gap-y-6 rounded-2xl border border-ev-border bg-linear-to-br from-white via-ev-legacy-soft to-ev-ice p-[clamp(1rem,3cqi,2rem)] @min-[48rem]:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <div class="min-w-0">
            <h1 class="page-title">Legacy Insights</h1>
            <p class="mt-2 max-w-md text-sm leading-6 text-ev-neutral-ink">Teruskan evaluasi yang disetujui dari edisi sebelumnya menjadi tugas untuk panitia saat ini.</p>
            @if($canManage)
                <x-button :href="route('evaluations.create', $event)" class="mt-4">Buat evaluasi</x-button>
            @endif
        </div>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 border-t border-ev-border pt-4 @min-[48rem]:border-s @min-[48rem]:border-t-0 @min-[48rem]:ps-6 @min-[48rem]:pt-0">
            @foreach([
                ['Evaluasi diwariskan', $inheritedTotal],
                ['Tindak lanjut aktif', $activeFollowUps],
                ['Evaluasi edisi ini', $evaluationsTotal],
                ['Disetujui di edisi ini', $approvedEvaluations],
            ] as [$label, $count])
                <div class="flex min-w-0 flex-col-reverse gap-1">
                    <dt class="text-xs leading-5 text-ev-neutral-ink">{{ $label }}</dt>
                    <dd class="type-section tabular-nums text-ev-ink">{{ $count }}</dd>
                </div>
            @endforeach
        </dl>
    </header>

    <x-list-search :action="route('workspace.legacy', $event)" label="Cari masalah atau rekomendasi" :value="$search" class="mb-5" />

    <section class="max-w-[58rem]" aria-labelledby="inherited-heading">
        <h2 id="inherited-heading" class="mb-4">Pembelajaran dari edisi sebelumnya</h2>
        @forelse($inherited->getCollection()->groupBy('event_id') as $editionLessons)
            <section class="mb-7" aria-labelledby="insight-edition-{{ $editionLessons->first()->event_id }}">
                <h3 id="insight-edition-{{ $editionLessons->first()->event_id }}" class="mb-3 wrap-anywhere text-base! font-medium! text-ev-neutral-ink">Evaluasi dari @display($editionLessons->first()->event->title)</h3>
                <div class="grid gap-5">
                    @foreach($editionLessons as $lesson)
                        @php($action = $actions->get($lesson->id))
                        <article class="legacy-knowledge-record min-w-0 rounded-xl border border-ev-border bg-ev-surface p-[clamp(1.125rem,2.6cqi,1.75rem)]">
                            <x-lesson-content :lesson="$lesson" editorial />
                            <x-lesson-attachments :event="$event" :lesson="$lesson" class="mt-6" />
                            @if($action)
                                <x-legacy-action :event="$event" :task="$action" editorial />
                            @else
                                <div class="mt-6">
                                    <p class="text-base font-semibold text-ev-ink">Tindak lanjut</p>
                                    @if($canManage)
                                        <x-button :href="route('legacy.action.create', [$event, $lesson])" class="mt-2">Buat Tindak Lanjut</x-button>
                                    @else
                                        <p class="mt-1 text-sm text-ev-neutral-ink">Belum ada tindak lanjut.</p>
                                    @endif
                                </div>
                            @endif
                            <footer class="mt-5 flex flex-wrap items-center gap-x-5">
                                <x-lesson-attribution :lesson="$lesson" editorial />
                                <a class="ev-link inline-flex min-h-11 items-center rounded-sm text-sm text-ev-neutral-ink hover:underline" href="{{ route('legacy.source', [$event, $lesson]) }}">Lihat sumber evaluasi</a>
                            </footer>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl bg-ev-legacy-soft p-4 text-sm leading-6 text-ev-neutral-ink">
                @if($inherited->total())
                    Halaman ini kosong. <a class="ev-link font-medium text-ev-primary underline" href="{{ $inherited->withQueryString()->url(1) }}">Buka halaman pertama</a>.
                @elseif($search !== '')
                    Tidak ada evaluasi warisan yang cocok.
                @else
                    Belum ada evaluasi yang diwariskan ke edisi ini.
                @endif
            </div>
        @endforelse
        @if($inherited->hasPages())
            <div class="mt-5">{{ $inherited->withQueryString()->links() }}</div>
        @endif
    </section>

    <section class="mt-9" aria-labelledby="evaluations-heading">
        <h2 id="evaluations-heading" class="mb-4">Evaluasi edisi ini</h2>
        <div class="grid gap-4">
            @forelse($lessons as $lesson)
                <article class="min-w-0 rounded-xl border border-ev-border bg-ev-surface p-[clamp(1rem,2.4cqi,1.5rem)]">
                    <x-lesson-attribution :lesson="$lesson" class="mb-4" />
                    <x-lesson-content :lesson="$lesson" />
                    <x-lesson-attachments :event="$event" :lesson="$lesson" class="mt-5" />
                    @if(!$lesson->approved_at && ($canManage || $canApprove))
                        <footer class="mt-4 flex flex-wrap gap-3">
                            @if($canManage)
                                <x-button variant="secondary" :href="route('evaluations.edit', [$event, $lesson])">Edit evaluasi</x-button>
                            @endif
                            @if($canApprove)
                                <form method="POST" action="{{ route('evaluations.approve', [$event, $lesson]) }}">
                                    @csrf
                                    @if($lesson->attachments->isNotEmpty())
                                        <p class="mb-2 text-sm text-ev-neutral-ink">Setelah disetujui, lampiran juga dapat dibuka oleh panitia edisi berikutnya yang berwenang.</p>
                                    @endif
                                    <x-button>Setujui untuk diwariskan</x-button>
                                </form>
                            @endif
                        </footer>
                    @endif
                </article>
            @empty
                @if($lessons->total())
                    <p class="text-sm text-ev-neutral-ink">Halaman ini kosong. <a class="ev-link font-medium text-ev-primary underline" href="{{ $lessons->withQueryString()->url(1) }}">Buka halaman pertama</a>.</p>
                @elseif($search !== '')
                    <p class="text-sm text-ev-neutral-ink">Tidak ada evaluasi edisi ini yang cocok.</p>
                @else
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-xl bg-ev-ice p-4 @min-[40rem]:px-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-white text-ev-info"><x-icon name="book" size="21" /></span>
                        <div class="min-w-0 flex-1 basis-48">
                            <h3>Belum ada evaluasi untuk edisi ini.</h3>
                            @if($canManage)
                                <p class="mt-1 text-sm leading-6 text-ev-neutral-ink">Catat masalah dan rekomendasi agar dapat diwariskan ke edisi berikutnya.</p>
                            @endif
                        </div>
                        @if($canManage)
                            <x-button :href="route('evaluations.create', $event)">Buat evaluasi</x-button>
                        @endif
                    </div>
                @endif
            @endforelse
        </div>
        @if($lessons->hasPages())
            <div class="mt-5">{{ $lessons->withQueryString()->links() }}</div>
        @endif
    </section>
</div>
@endsection
