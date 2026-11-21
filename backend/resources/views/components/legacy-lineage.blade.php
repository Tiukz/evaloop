@props(['event', 'source'])
@php
    $steps = [
        ['Edisi sumber', $source->title, 'event'],
        ['Evaluasi disetujui', null, 'task'],
        ['Edisi penerima', $event->title, 'event'],
    ];
@endphp

<ol role="list" class="legacy-lineage mb-4 grid gap-3 rounded-xl bg-ev-legacy-soft p-4 @min-[40rem]:grid-cols-3"
    aria-label="Perjalanan pembelajaran antaredisi">
    @foreach($steps as [$label, $title, $icon])
        <li class="relative flex min-w-0 items-start gap-3 not-last:before:absolute not-last:before:-bottom-7 not-last:before:start-4 not-last:before:top-4 not-last:before:w-px not-last:before:bg-ev-border @min-[40rem]:flex-col @min-[40rem]:items-center @min-[40rem]:gap-2 @min-[40rem]:text-center @min-[40rem]:not-last:before:bottom-auto @min-[40rem]:not-last:before:start-1/2 @min-[40rem]:not-last:before:-end-[calc(50%+0.75rem)] @min-[40rem]:not-last:before:h-px @min-[40rem]:not-last:before:w-auto">
            <span @class([
                'z-10 grid size-8 shrink-0 place-items-center rounded-full',
                'bg-white text-ev-info' => $title !== null,
                'bg-ev-success-soft text-ev-success' => $title === null,
            ])><x-icon :name="$icon" size="16" /></span>
            <div class="min-w-0 @max-[40rem]:pt-1">
                @if($title !== null)
                    <span class="block text-xs leading-5 text-ev-neutral-ink">{{ $label }}</span>
                    <strong class="block wrap-anywhere text-sm font-semibold text-ev-ink">@display($title)</strong>
                @else
                    <strong class="block text-sm font-semibold text-ev-success">{{ $label }}</strong>
                @endif
            </div>
        </li>
    @endforeach
</ol>
