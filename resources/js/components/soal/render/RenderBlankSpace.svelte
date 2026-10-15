<script lang="ts">
    // Renderer: Blank Space (isi titik-titik)
    let {
        detail,
        jawaban = $bindable([] as string[]),
        disabled = false,
    }: {
        detail: { teks: string; kunci: string[] };
        jawaban?: string[];
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.teks ?? '')) {
            initialized = true;
            const count = ((safe.teks ?? '').match(/\[___\]/g) || []).length;
            if (jawaban.length !== count) {
                jawaban = new Array(count).fill('');
            }
        }
    });

    // Render teks dengan input di posisi [___]
    function parts(): ({ type: 'text'; content: string } | { type: 'blank'; index: number })[] {
        const result: ({ type: 'text'; content: string } | { type: 'blank'; index: number })[] = [];
        let idx = 0;
        for (const seg of (safe.teks ?? '').split('[___]')) {
            if (result.length > 0) result.push({ type: 'blank', index: idx++ });
            if (seg) result.push({ type: 'text', content: seg });
        }
        return result;
    }
</script>

<div class="lh-lg">
    {#each parts() as p}
        {#if p.type === 'text'}
            <span>{@html p.content}</span>
        {:else}
            <input class="form-control form-control-sm d-inline-block mx-1" style="width:150px"
                bind:value={jawaban[p.index]} disabled={disabled} placeholder="..." />
        {/if}
    {/each}
</div>
