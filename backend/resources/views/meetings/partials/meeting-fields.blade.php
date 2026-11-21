<div class="grid gap-4 @min-[36rem]:grid-cols-[minmax(0,1fr)_auto]">
    <x-input name="title" label="Judul rapat" :value="$meeting->title" required maxlength="180" />
    <x-input name="scheduled_at" label="Jadwal (WITA)" type="datetime-local"
             :value="$meeting->scheduled_at?->copy()->timezone('Asia/Makassar')->format('Y-m-d\TH:i')" required />
</div>
