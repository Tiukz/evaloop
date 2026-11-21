@props(['event', 'active' => 'overview'])
<nav class="workspace-module-navigation" aria-label="Workspace event">
@foreach(['overview' => ['Ringkasan', 'home'], 'conflicts' => ['Jadwal & bentrokan', 'radar'], 'meetings' => ['Meeting Hub', 'event'], 'tasks' => ['Tugas', 'task'], 'legacy' => ['Legacy Insights', 'book'], 'members' => ['Panitia', 'people']] as $key => [$label, $icon])
    @if($key !== 'conflicts' || auth()->user()->can('manage', $event))<a href="{{ route('workspace.'.$key, $event) }}" @if($active === $key) aria-current="page" @endif><x-icon :name="$icon" size="16" />{{ $label }}</a>@endif
@endforeach
</nav>
