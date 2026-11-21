@props(['name', 'label', 'id' => null])
@php
    $fieldId = $id ?? rtrim(str_replace(['[', ']'], ['-', ''], $name), '-');
    $errorName = rtrim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $fieldErrors = collect($errors->get($errorName))->merge(collect($errors->get($errorName.'.*'))->flatten())->unique();
@endphp
<div class="min-w-0">
    <label for="{{ $fieldId }}" class="block text-sm font-medium">{{ $label }}</label>
    <select id="{{ $fieldId }}" name="{{ $name }}"
        @if($fieldErrors->isNotEmpty()) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class(['ev-control mt-2 block min-h-11 w-full min-w-0 max-w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:outline-ev-primary', 'border-red-600' => $fieldErrors->isNotEmpty()]) }}>
        {{ $slot }}
    </select>
    @if($fieldErrors->isNotEmpty())<div id="{{ $fieldId }}-error" role="alert" class="mt-2 space-y-1 text-sm text-ev-danger">@foreach($fieldErrors as $message)<p>{{ $message }}</p>@endforeach</div>@endif
</div>
