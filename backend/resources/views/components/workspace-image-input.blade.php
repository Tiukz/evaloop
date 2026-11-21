@props(['name', 'label', 'description', 'required' => false])

<div class="min-w-0 space-y-2">
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>
    <input id="{{ $name }}" type="file" name="{{ $name }}" accept=".jpg,.jpeg,.png,.webp" @required($required)
        aria-describedby="{{ $name }}-help{{ $errors->has($name) ? ' '.$name.'-error' : '' }}"
        @error($name) aria-invalid="true" @enderror
        class="ev-control block min-h-11 w-full min-w-0 max-w-full rounded-lg border border-ev-border bg-white px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-ev-ice file:px-2 file:py-1 file:font-medium file:text-ev-ink">
    <p id="{{ $name }}-help" class="text-sm text-ev-neutral-ink">{{ $description }} JPG, PNG, atau WebP, maksimal 2 MB.</p>
    @error($name)<p id="{{ $name }}-error" class="text-sm text-ev-danger">{{ $message }}</p>@enderror
</div>
