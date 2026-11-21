@props(['name', 'label', 'type' => 'text', 'value' => ''])
@php($inputValue = old($name, $value))
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ is_scalar($inputValue) ? $inputValue : '' }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class(['ev-control mt-2 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-ev-primary focus:outline-ev-primary']) }}>
    @error($name) <p id="{{ $name }}-error" role="alert" class="mt-2 text-sm text-ev-danger">{{ $message }}</p> @enderror
</div>
