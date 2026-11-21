@props(['event'])
@if($event->poster_path)
    <div class="radar-poster bg-ev-ice!">
        <img src="{{ route('events.media.poster', $event->id) }}" alt="" class="absolute inset-0 size-full object-contain" decoding="async">
    </div>
@else
@php
    $start = $event->starts_at->copy()->timezone('Asia/Makassar')->locale('id');
    $clean = fn ($text) => preg_replace('/^\[(?:SIMULASI|TECFEST)\]\s*/u', '', $text ?? '');
    $scope = $event->organization->studyProgram?->name ?? $event->organization->faculty?->name ?? 'Tingkat universitas';
    $posterWords = preg_split('/\s+/u', $clean($event->title), 2);
    $posterVariant = $event->organization_id % 3;
@endphp
    <div class="radar-poster radar-poster-{{ $posterVariant }}" aria-hidden="true">
        <div class="radar-poster-top"><span>evaloop.</span><span>{{ $start->format('Y') }}</span></div>
        <span class="radar-poster-orbit"></span>
        <div class="radar-poster-type"><strong>{{ $posterWords[0] }}</strong>@if(isset($posterWords[1]))<span>{{ $posterWords[1] }}</span>@endif</div>
        <div class="radar-poster-bottom"><span>{{ $clean($scope) }}</span><span>{{ $start->format('d') }} / {{ $start->format('m') }}</span></div>
        
    </div>
@endif
