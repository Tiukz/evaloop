@php
    $comparisons = $collisionPreview['comparisons'] ?? null;
    $overlaps = collect($collisionPreview['warnings'] ?? [])
        ->filter(fn ($warning) => count($warning['types']) > 1)->take(2);
    $overlapLabels = ['venue_overlap' => 'Lokasi sama', 'audience_overlap' => 'Sasaran beririsan'];
@endphp
@if($comparisons?->total() || ($collisionPreview['unavailableReason'] ?? null))
    <section aria-labelledby="ecc-attention-heading">
        <div class="ecc-section-head">
            <h2 id="ecc-attention-heading">Perlu perhatian</h2>
            <a class="ecc-link ev-link" href="{{ route('workspace.conflicts', $event) }}">Tinjau jadwal</a>
        </div>
        @if($collisionPreview['unavailableReason'])
            <p class="ecc-quiet">{{ $collisionPreview['unavailableReason'] }}</p>
        @else
            <div class="rounded-xl border border-ev-border bg-ev-surface px-4 py-3.5">
                <p class="font-semibold text-ev-info">{{ $comparisons->total() }} jadwal publik beririsan</p>
                @if($overlaps->isNotEmpty())
                    <ul class="mt-2 space-y-2 text-sm" role="list">
                        @foreach($overlaps as $warning)
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="min-w-0 wrap-anywhere">@display($warning['event']->title)</span>
                                <span class="text-ev-warning">{{ implode(' · ', array_intersect_key($overlapLabels, array_flip($warning['types']))) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </section>
@endif
