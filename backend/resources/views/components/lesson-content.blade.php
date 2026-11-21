@props(['lesson', 'editorial' => false])
@if($editorial)
    @php
        $problem = trim($lesson->problem);
        $opening = $problem;
        $detail = null;
        $hasTitle = Str::length($problem) <= 160;
        if (! $hasTitle) {
            [$opening, $detail] = array_pad(preg_split('/\R\h*\R|(?<=[.!?])\s+(?=\p{Lu})/u', $problem, 2), 2, null);
            $hasTitle = Str::length($opening) >= 24 && Str::length($opening) <= 160;
        }
    @endphp
    <div class="max-w-[68ch] space-y-6">
        <div class="min-w-0">
            @if($hasTitle)
                <p class="mb-1 text-base font-semibold text-ev-neutral-ink">Permasalahan</p>
                <h4 class="wrap-anywhere whitespace-pre-wrap text-[1.25rem]! sm:text-[1.375rem]!">@display($opening)</h4>
                @if(filled($detail))
                    <p class="mt-2 wrap-anywhere whitespace-pre-wrap text-base leading-relaxed text-ev-neutral-ink sm:text-[1.0625rem]">@display($detail)</p>
                @endif
            @else
                <h4 class="text-[1.25rem]! sm:text-[1.375rem]!">Permasalahan</h4>
                <p class="mt-2 wrap-anywhere whitespace-pre-wrap text-base leading-relaxed text-ev-neutral-ink sm:text-[1.0625rem]">@display($problem)</p>
            @endif
        </div>
        <div class="min-w-0">
            <p class="mb-1 text-base font-semibold text-ev-info">Rekomendasi</p>
            <p class="wrap-anywhere whitespace-pre-wrap text-[1.0625rem] font-medium leading-relaxed text-ev-ink sm:text-lg">@display($lesson->recommendation)</p>
        </div>
    </div>
@else
    <dl class="max-w-[72ch] space-y-4">
        <div class="min-w-0">
            <dt class="text-sm font-semibold text-ev-ink">Permasalahan</dt>
            <dd class="mt-1 wrap-anywhere whitespace-pre-wrap leading-7 text-ev-neutral-ink">@display($lesson->problem)</dd>
        </div>
        <div class="min-w-0">
            <dt class="text-sm font-semibold text-ev-ink">Rekomendasi</dt>
            <dd class="mt-1 wrap-anywhere whitespace-pre-wrap font-medium leading-7 text-ev-ink">@display($lesson->recommendation)</dd>
        </div>
    </dl>
@endif
