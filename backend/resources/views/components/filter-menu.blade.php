@props(['label', 'value' => null, 'active' => false, 'open' => false, 'wide' => false, 'clearUrl' => null])
<details @class(['radar-filter-menu', 'radar-filter-wide' => $wide, 'has-selection' => $active]) data-filter-menu @if($open) open @endif>
    <summary class="radar-filter-trigger"><span>{{ $label }}@if($active)<strong>{{ $value }}</strong>@endif</span><x-icon name="chevron" size="14" /></summary>
    <div class="ev-menu-panel radar-filter-panel">{{ $slot }}<div class="filter-panel-actions">@if($active && $clearUrl)<a href="{{ $clearUrl }}" class="ev-link mr-auto inline-flex min-h-11 items-center text-sm text-ev-primary">Hapus filter</a>@endif<x-button size="sm">Terapkan</x-button></div></div>
</details>
