<script lang="ts">
    // Renderer: Menjodohkan
    let {
        detail,
        jawaban = $bindable({} as Record<number, string>),
        disabled = false,
    }: {
        detail: { pasangan: { kiri: string; kanan: string }[] };
        jawaban?: Record<number, string>;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    // Acak opsi kanan untuk siswa (sekali saja)
    let kananAcak = $state<string[]>(
        [...(safe.pasangan ?? []).map(p => p.kanan)].sort(() => Math.random() - 0.5)
    );
</script>

<div class="row g-3">
    <div class="col-md-6">
        {#each (safe.pasangan ?? []) as p, i}
            <div class="border rounded p-2 mb-2 d-flex gap-2 align-items-center">
                <span class="badge bg-secondary">{i + 1}</span>
                <span>{@html p.kiri}</span>
            </div>
        {/each}
    </div>
    <div class="col-md-6">
        {#each (safe.pasangan ?? []) as _, i}
            <div class="mb-2">
                <select class="form-select form-select-sm" bind:value={jawaban[i]} disabled={disabled}>
                    <option value="">— Pilih pasangan untuk {i + 1} —</option>
                    {#each kananAcak as k}<option value={k}>{@html k}</option>{/each}
                </select>
            </div>
        {/each}
    </div>
</div>
