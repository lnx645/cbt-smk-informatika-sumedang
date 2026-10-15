<script lang="ts">
    // Renderer: Mengelompokkan (siswa pilih kelompok per item)
    let {
        detail,
        jawaban = $bindable({} as Record<string, string>),
        disabled = false,
    }: {
        detail: { kelompok: { nama: string; items: string[] }[] };
        jawaban?: Record<string, string>;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    // Kumpulkan semua item, acak (sekali saja)
    let semuaItem = $state<string[]>([]);
    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.kelompok ?? []).length > 0) {
            initialized = true;
            const items: string[] = [];
            for (const k of safe.kelompok ?? []) for (const it of k.items) if (it.trim()) items.push(it);
            semuaItem = [...items].sort(() => Math.random() - 0.5);
        }
    });
</script>

<div class="alert alert-light border small">Pilih kelompok untuk setiap item.</div>
{#each semuaItem as item, i}
    <div class="border rounded p-2 mb-2 d-flex gap-2 align-items-center">
        <span class="flex-grow-1">{@html item}</span>
        <select class="form-select form-select-sm" style="max-width:200px" bind:value={jawaban[item]} disabled={disabled}>
            <option value="">— Pilih kelompok —</option>
            {#each (safe.kelompok ?? []) as k}<option value={k.nama}>{k.nama}</option>{/each}
        </select>
    </div>
{/each}
