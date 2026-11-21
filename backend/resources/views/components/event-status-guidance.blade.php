@props(['event', 'showDescription' => true, 'compact' => false, 'subtle' => false])
@php
    $draft = $event->status === 'draft';
    $completed = $event->status === 'completed';
    $canPublish = $draft && auth()->user()->can('publish', $event);
    $canComplete = $event->canBeCompleted() && auth()->user()->can('complete', $event);
    $canCancel = in_array($event->status, ['draft', 'published'], true) && auth()->user()->can('cancel', $event);
    $menuLinkClass = 'ev-link flex min-h-11 items-center rounded-lg px-3 text-sm font-medium hover:bg-ev-ice';
    $primaryAction = match (true) {
        $canPublish => ['workspace.publish.confirm', 'Publikasikan event'],
        $canComplete => ['workspace.complete.confirm', 'Tandai selesai'],
        $completed => ['workspace.legacy', 'Buka evaluasi'],
        default => null,
    };
@endphp
@if($compact)
    @if($primaryAction || $canCancel)
        <details {{ $attributes->class(['relative shrink-0']) }} data-filter-menu>
            <summary @class([
                'flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-lg px-2 text-sm font-medium [&::-webkit-details-marker]:hidden',
                'gap-2 text-ev-muted transition-colors hover:bg-ev-mist hover:text-ev-ink active:bg-ev-primary-soft motion-reduce:transition-none' => $subtle,
                'gap-1 text-ev-primary hover:bg-ev-primary-soft' => !$subtle,
            ]) aria-label="Tindakan untuk @display($event->title)">
                <span @class(['hidden @min-[40rem]:inline' => !$subtle])>Tindakan</span>
                <x-icon name="chevron" size="17" />
            </summary>
            <div class="ev-menu-panel w-48 space-y-0 p-1 [right:calc(0px_-_var(--filter-offset,0px))]">
                @if($primaryAction)
                    <a class="{{ $menuLinkClass }} text-ev-primary" href="{{ route($primaryAction[0], $event) }}">{{ $primaryAction[1] }}</a>
                @endif
                @if($canCancel)
                    <a class="{{ $menuLinkClass }} text-ev-danger hover:text-ev-danger active:text-ev-danger" href="{{ route('workspace.cancel.confirm', $event) }}">Batalkan event</a>
                @endif
            </div>
        </details>
    @endif
@elseif($showDescription || $primaryAction || $canCancel)
<div {{ $attributes->class([
    'flex min-w-0 flex-wrap items-center gap-3 border-t border-ev-border px-4 py-3 text-sm',
    'justify-between' => $showDescription,
    'justify-end' => !$showDescription,
]) }} aria-label="Pengelolaan status kegiatan">
    @if($showDescription)
    <div class="min-w-0 max-w-2xl text-slate-600">
        @if($event->status === 'draft')
            <p><strong class="font-semibold text-ev-ink">Draft — belum tampil di Event Radar.</strong> Publikasikan setelah informasi dan jadwal siap.</p>
        @elseif($event->status === 'published')
            <p><strong class="font-semibold text-ev-ink">Dipublikasikan.</strong> Admin organisasi dapat menandai kegiatan ini selesai secara manual.</p>
        @elseif($event->status === 'completed')
            <p><strong class="font-semibold text-ev-ink">Selesai — edisi ini menjadi arsip.</strong> Lanjutkan dengan evaluasi dan Legacy Insights.</p>
        @elseif($event->status === 'cancelled')
            <p><strong class="font-semibold text-ev-ink">Dibatalkan.</strong> Catatan kegiatan tetap tersimpan sebagai arsip.</p>
        @endif
    </div>
    @endif
    <div class="flex flex-wrap items-center gap-3">
        @if($canPublish)
            <x-button size="sm" :href="route('workspace.publish.confirm', $event)">Publikasikan event</x-button>
        @elseif($canComplete)
            <x-button size="sm" :href="route('workspace.complete.confirm', $event)">Tandai selesai</x-button>
        @elseif($draft && $showDescription)
            <p class="text-xs text-slate-600">Publikasi dilakukan admin organisasi.</p>
        @elseif($completed)
            <a class="ev-link inline-flex min-h-11 items-center text-sm font-medium text-ev-primary" href="{{ route('workspace.legacy', $event) }}">Buka evaluasi</a>
        @endif
        @if($canCancel)
            <a class="ev-link inline-flex min-h-11 items-center text-xs text-ev-danger hover:text-ev-danger active:text-ev-danger" href="{{ route('workspace.cancel.confirm', $event) }}">Batalkan event</a>
        @endif
    </div>
</div>
@endif
