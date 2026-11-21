<x-form-section title="Poster event">
    @if($event->poster_path)
        <img src="{{ route('events.media.poster', $event->id) }}" alt="Poster saat ini" class="max-h-40 max-w-full rounded-lg border border-ev-border object-contain">
    @endif
    <x-workspace-image-input name="poster" :label="$event->poster_path ? 'Ganti gambar poster (opsional)' : 'Unggah gambar poster (opsional)'" description="Poster tampil di Event Radar setelah publikasi." />
</x-form-section>
