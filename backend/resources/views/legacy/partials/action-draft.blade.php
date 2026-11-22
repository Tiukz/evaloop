<div id="legacy-action-draft" class="mt-4 grid justify-items-start gap-2" hidden
    data-url="{{ route('legacy.action.draft', [$event, $lesson]) }}">
    <x-button type="button" variant="secondary" size="sm" loading-label="Menyusun draf…">
        <span class="inline-flex items-center gap-2">
            <x-icon name="sparkles" size="16" />
            Bantu Susun dengan AI
        </span>
    </x-button>
    <p class="text-sm text-ev-neutral-ink" role="status"></p>
    <div class="w-full" data-description hidden>
        <label for="ai-draft-description" class="block text-sm font-medium">Draf deskripsi</label>
        <textarea id="ai-draft-description" maxlength="1200" rows="4"
            class="ev-control mt-2 block w-full rounded-lg border border-ev-border bg-ev-surface px-3 py-2"></textarea>
    </div>
</div>
<script>
    (() => {
        const panel = document.getElementById('legacy-action-draft');
        const form = panel.closest('form');
        const button = panel.querySelector('button');
        const status = panel.querySelector('[role="status"]');
        const title = form.elements.namedItem('title');
        const description = panel.querySelector('textarea');
        const descriptionField = panel.querySelector('[data-description]');
        const initialTitle = title.value;

        panel.hidden = false;
        button.addEventListener('click', async () => {
            if (button.getAttribute('aria-busy') === 'true') return;
            if ((title.value !== initialTitle || !descriptionField.hidden)
                && !window.confirm('Ganti judul dan draf deskripsi dengan hasil AI baru?')) return;

            const currentTitle = title.value;
            const currentDescription = description.value;
            status.textContent = 'Sedang menyusun draf…';
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('aria-disabled', 'true');

            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': form.elements.namedItem('_token').value,
                    },
                    signal: AbortSignal.timeout(16000),
                });
                if (!response.ok) {
                    status.textContent = ({
                        401: 'Sesi berakhir. Masuk kembali untuk menggunakan bantuan AI.',
                        403: 'Anda tidak memiliki izin untuk menyusun draf evaluasi ini.',
                        409: 'Tindak lanjut sudah dibuat. Muat ulang halaman untuk membukanya.',
                        419: 'Sesi berakhir. Salin isian Anda sebelum memuat ulang halaman.',
                        429: 'Terlalu banyak percobaan. Coba lagi dalam satu menit.',
                    })[response.status] ?? 'Draf AI belum tersedia. Coba lagi atau isi tugas secara manual.';
                    return;
                }
                const {draft} = await response.json();
                if (typeof draft?.title !== 'string' || typeof draft?.description !== 'string') {
                    throw new Error('Invalid draft');
                }
                if (title.value !== currentTitle || description.value !== currentDescription) {
                    status.textContent = 'Isian berubah selama AI bekerja. Isian Anda tetap dipertahankan.';
                    return;
                }

                title.value = draft.title;
                description.value = draft.description;
                descriptionField.hidden = false;
                status.textContent = '';
            } catch {
                status.textContent = 'Draf AI belum tersedia. Coba lagi atau isi tugas secara manual.';
            } finally {
                button.removeAttribute('aria-busy');
                button.removeAttribute('aria-disabled');
            }
        });
    })();
</script>
