<script lang="ts">
    // Renderer: Image Labeling (siswa isi label per titik)
    let {
        detail,
        jawaban = $bindable({} as Record<number, string>),
        disabled = false,
    }: {
        detail: { image: string; markers: { x: number; y: number; label: string }[] };
        jawaban?: Record<number, string>;
        disabled?: boolean;
    } = $props();

    // Defensive: pastikan detail tidak null
    const safe = detail ?? {};
</script>

<div class="position-relative border rounded mb-3 d-inline-block" style="max-width:100%">
    <img src={(safe.image ?? "")} alt="Soal" style="max-width:100%;display:block" />
    {#each (safe.markers ?? []) as m, i}
        <div class="position-absolute badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
            style="left:{m.x}%;top:{m.y}%;width:24px;height:24px;transform:translate(-50%,-50%)">{i + 1}</div>
    {/each}
</div>

{#each (safe.markers ?? []) as m, i}
    <div class="row g-2 mb-2 align-items-center">
        <div class="col-auto"><span class="badge bg-primary rounded-circle">{i + 1}</span></div>
        <div class="col"><input class="form-control form-control-sm" bind:value={jawaban[i]} disabled={disabled} placeholder="Label untuk titik {i + 1}" /></div>
    </div>
{/each}
