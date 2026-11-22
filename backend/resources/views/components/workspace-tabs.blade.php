@props(['event', 'active' => 'overview'])
<nav class="workspace-module-navigation" aria-label="Navigasi kegiatan">
@foreach(['overview' => ['Ringkasan', 'home'], 'conflicts' => ['Jadwal & Bentrokan', 'radar'], 'meetings' => ['Meeting Hub', 'event'], 'tasks' => ['Tugas & Progres', 'task'], 'legacy' => ['Legacy Insights', 'book'], 'members' => ['Panitia', 'people']] as $key => [$label, $icon])
    @if($key !== 'conflicts' || auth()->user()->can('manage', $event))<a href="{{ route('workspace.'.$key, $event) }}" @if($active === $key) aria-current="page" @endif><x-icon :name="$icon" size="16" class="hidden sm:block" />{{ $label }}</a>@endif
@endforeach
</nav>
