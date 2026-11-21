<label for="attachment-files" class="block text-sm font-semibold">Pilih file lampiran{{ $required ? '' : ' (opsional)' }}</label>
<input id="attachment-files" type="file" name="attachments[]" multiple @required($required) accept=".pdf,.docx,.jpg,.jpeg,.png,.webp"
    aria-describedby="attachment-help{{ $errors->has('attachments') || $errors->has('attachments.*') ? ' attachment-errors' : '' }}"
    @if($errors->has('attachments') || $errors->has('attachments.*')) aria-invalid="true" @endif
    class="ev-control block min-h-11 min-w-0 w-full max-w-full rounded-lg border border-ev-border bg-white px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-ev-legacy-soft file:px-2 file:py-1 file:text-sm file:font-medium file:text-ev-ink">
<p id="attachment-help" class="text-sm text-ev-neutral-ink">PDF, DOCX, JPG, JPEG, PNG, atau WEBP. Maksimal 5 file per evaluasi, masing-masing hingga {{ \Illuminate\Support\Number::fileSize(config('evaloop.lesson_attachment_max_kb') * 1024) }}.</p>
@if($errors->has('attachments') || $errors->has('attachments.*'))
    <p class="text-sm text-ev-neutral-ink">Pilih kembali file sebelum mencoba lagi.</p>
    <ul id="attachment-errors" class="space-y-1 text-sm text-ev-danger">
        @foreach(array_unique(array_merge($errors->get('attachments'), ...array_values($errors->get('attachments.*')))) as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
