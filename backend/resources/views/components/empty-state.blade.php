@props(['title', 'message'])
<section {{ $attributes->class(['rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center']) }}>
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ $message }}</p>
    {{ $slot }}
</section>
