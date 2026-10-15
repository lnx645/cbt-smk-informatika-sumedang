<script lang="ts">
    // Renderer: Mengurutkan (siswa susun ulang)
    let {
        detail,
        jawaban = $bindable([] as string[]),
        disabled = false,
    }: {
        detail: { urutan: string[] };
        jawaban?: string[];
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};

    // Acak awal (sekali saja saat init)
    let initialized = $state(false);
    $effect(() => {
        if (!initialized && (safe.urutan ?? []).length > 0) {
            initialized = true;
            if (jawaban.length === 0) {
                jawaban = [...safe.urutan ?? []].sort(() => Math.random() - 0.5);
            }
        }
    });

    function pindah(i: number, dir: number) {
        if (disabled) return;
        const j = i + dir;
        if (j < 0 || j >= jawaban.length) return;
        const arr = [...jawaban];
        [arr[i], arr[j]] = [arr[j], arr[i]];
        jawaban = arr;
    }
</script>

<div class="alert alert-light border small">Susun dari atas ke bawah sesuai urutan yang benar.</div>
{#each jawaban as item, i}
    <div class="border rounded p-2 mb-2 d-flex gap-2 align-items-center">
        <span class="badge bg-primary">{i + 1}</span>
        <span class="flex-grow-1">{@html item}</span>
        {#if !disabled}
            <button class="btn btn-sm btn-link p-0" onclick={() => pindah(i, -1)} disabled={i === 0} title="Naik">
                <i class="bi bi-arrow-up"></i>
            </button>
            <button class="btn btn-sm btn-link p-0" onclick={() => pindah(i, 1)} disabled={i === jawaban.length - 1} title="Turun">
                <i class="bi bi-arrow-down"></i>
            </button>
        {/if}
    </div>
{/each}
