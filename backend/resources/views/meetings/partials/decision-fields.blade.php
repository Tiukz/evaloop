<div class="grid gap-4">
    <x-textarea name="summary" label="Keputusan" :value="$decision->summary ?? ''" required maxlength="15000" rows="2"
                class="min-h-20 max-h-80 [field-sizing:content]" />
    <x-textarea name="rationale" label="Pertimbangan (opsional)" :value="$decision->rationale ?? ''" maxlength="15000" rows="2"
                class="min-h-16 max-h-64 [field-sizing:content]" />
</div>
