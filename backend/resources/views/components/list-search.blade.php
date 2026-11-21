@props(['action', 'label', 'value'])
<form method="GET" action="{{ $action }}" aria-label="{{ $label }}" {{ $attributes->class(['flex max-w-xl flex-wrap items-center gap-2']) }}>
    <label class="relative min-w-36 flex-1">
        <span class="sr-only">{{ $label }}</span>
        <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-ev-neutral-ink"><x-icon name="search" size="17" /></span>
        <input class="ev-control min-h-11 w-full rounded-lg border border-ev-border bg-white pe-3 ps-9 text-sm"
               type="search" name="q" value="{{ $value }}" maxlength="120" placeholder="{{ $label }}…">
    </label>
    <x-button variant="secondary" size="sm" loading-label="Mencari…">Cari</x-button>
    @if($value !== '')
        <a class="section-link ev-link px-2" href="{{ $action }}">Reset</a>
    @endif
</form>
