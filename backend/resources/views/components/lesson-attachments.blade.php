@props(['event', 'lesson', 'editable' => false])

@if($editable || $lesson->attachments->isNotEmpty())
    <div {{ $attributes }}>
        <p class="text-base font-semibold text-ev-ink">
            Lampiran evaluasi
            <span class="ms-1 text-sm font-normal tabular-nums text-ev-neutral-ink">({{ $lesson->attachments->count() }}{{ $editable ? '/5' : '' }})</span>
        </p>
        @if($lesson->attachments->isNotEmpty())
            <ul class="mt-2 divide-y divide-ev-border" role="list">
                @foreach($lesson->attachments as $attachment)
                    @php($image = str_starts_with($attachment->mime_type, 'image/'))
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2">
                        <div class="flex min-w-0 flex-1 basis-44 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-ev-legacy-soft text-ev-info">
                                <x-icon :name="$image ? 'image' : 'file'" size="18" />
                            </span>
                            <div class="min-w-0">
                                <p class="wrap-anywhere text-sm font-semibold text-ev-ink">{{ $attachment->original_name }}</p>
                                <p class="text-sm text-ev-neutral-ink">{{ strtoupper(pathinfo($attachment->path, PATHINFO_EXTENSION)) }} · {{ \Illuminate\Support\Number::fileSize($attachment->size_bytes) }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-2">
                            @if($attachment->canPreview())
                                <x-button variant="ghost" :href="route('legacy.attachments.show', [$event, $lesson, $attachment, 'preview'])" target="_blank" rel="noopener" :aria-label="'Pratinjau '.$attachment->original_name.' (tab baru)'">Pratinjau</x-button>
                            @endif
                            <x-button variant="secondary" :href="route('legacy.attachments.show', [$event, $lesson, $attachment, 'download'])" :aria-label="'Unduh '.$attachment->original_name">Unduh</x-button>
                        </div>
                        @if($editable)
                            <details class="basis-full">
                                <summary class="ev-link inline-flex min-h-11 cursor-pointer items-center rounded-sm text-sm text-ev-danger">Hapus lampiran</summary>
                                <form method="POST" action="{{ route('evaluations.attachments.destroy', [$event, $lesson, $attachment]) }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 pb-2">
                                    @csrf
                                    @method('DELETE')
                                    <p class="text-sm text-ev-neutral-ink">Hapus file ini dari evaluasi?</p>
                                    <x-button variant="danger" loading-label="Menghapus…">Ya, hapus</x-button>
                                </form>
                            </details>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif($editable)
            <p class="mt-1 text-sm text-ev-neutral-ink">Belum ada lampiran.</p>
        @endif
    </div>
@endif
