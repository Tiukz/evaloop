@if($inheritedLessons->isNotEmpty())
    <section class="grid gap-4" aria-labelledby="ecc-inherited-heading">
        <div class="flex flex-wrap items-center justify-between gap-x-5">
            <h2 id="ecc-inherited-heading">Pembelajaran edisi sebelumnya</h2>
            <a class="ecc-link ev-link" href="{{ route('workspace.legacy', $event) }}">Lihat Legacy Insights</a>
        </div>
        <div class="divide-y divide-ev-border rounded-xl border border-ev-border bg-ev-surface px-[clamp(1rem,4cqi,2rem)]">
            @foreach($inheritedLessons as $lesson)
                @php($followUp = $lesson->inspiredTasks->first())
                <article class="grid min-w-0 gap-4 py-[clamp(1.25rem,3.5cqi,1.75rem)]">
                    <div class="grid max-w-[68ch] gap-2">
                        <p class="wrap-anywhere text-sm leading-5 text-ev-neutral-ink">@display($lesson->event->title)</p>
                        <h3 class="type-card line-clamp-2 wrap-anywhere">@display($lesson->problem)</h3>
                    </div>
                    <div class="grid max-w-[68ch] gap-1.5">
                        <p class="text-sm font-semibold text-ev-neutral-ink">Rekomendasi</p>
                        <p class="line-clamp-3 wrap-anywhere text-[0.9375rem] leading-relaxed text-ev-ink">@display($lesson->recommendation)</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        @if($followUp)
                            <a class="ecc-link ev-link gap-2" href="{{ route('workspace.tasks', [$event, 'task' => $followUp->id]) }}#task-{{ $followUp->id }}">
                                <x-icon name="task" size="15" /> Lihat tugas tindak lanjut
                            </a>
                        @elseif($canManage)
                            @can('actOn', [$lesson, $event])
                                <x-button size="sm" :href="route('legacy.action.create', [$event, $lesson])">Buat Tindak Lanjut</x-button>
                            @endcan
                        @endif
                        <a class="ecc-link ev-link" href="{{ route('legacy.source', [$event, $lesson]) }}">Baca evaluasi</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
