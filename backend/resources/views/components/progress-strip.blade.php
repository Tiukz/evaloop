@props(['todo' => 0, 'inProgress' => 0, 'done' => 0])
@php($total = $todo + $inProgress + $done)
@if($total > 0)
<div {{ $attributes }}>
    <div class="mb-3 flex items-baseline justify-between gap-3 text-sm"><span class="font-medium">Progres tugas</span><span class="tabular-nums text-slate-500">{{ $done }}/{{ $total }} selesai</span></div>
    <x-completion-meter :done="$done" :total="$total" :in-progress="$inProgress" />
    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600"><span>{{ $todo }} Belum dimulai</span><span>{{ $inProgress }} Sedang dikerjakan</span><span>{{ $done }} Selesai</span></div>
</div>
@else
<p class="text-sm leading-6 text-slate-500">Belum ada tugas untuk dirangkum.</p>
@endif
