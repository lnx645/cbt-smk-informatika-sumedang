<script lang="ts">
    // Dispatcher: render soal sesuai tipe. Reusable untuk preview guru & ujian siswa.
    import RenderPilihanGanda from './RenderPilihanGanda.svelte';
    import RenderMenjodohkan from './RenderMenjodohkan.svelte';
    import RenderIsian from './RenderIsian.svelte';
    import RenderMengurutkan from './RenderMengurutkan.svelte';
    import RenderPernyataan from './RenderPernyataan.svelte';
    import RenderMatrix from './RenderMatrix.svelte';
    import RenderBlankSpace from './RenderBlankSpace.svelte';
    import RenderImageLabeling from './RenderImageLabeling.svelte';
    import RenderListening from './RenderListening.svelte';
    import RenderMengelompokkan from './RenderMengelompokkan.svelte';

    let {
        tipe,
        detail,
        jawaban = $bindable(null),
        disabled = false,
        showKunci = false,
    }: {
        tipe: string;
        detail: Record<string, any> | null;
        jawaban?: any;
        disabled?: boolean;
        showKunci?: boolean;
    } = $props();

    const d = detail ?? {};
</script>

{#if tipe === 'pilihan_ganda' || tipe === 'pilihan_ganda_citra'}
    <RenderPilihanGanda detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'pilihan_ganda_kompleks'}
    <RenderPilihanGanda detail={d} kompleks bind:jawaban disabled={disabled} />
{:else if tipe === 'menjodohkan' || tipe === 'menjodohkan_banyak'}
    <RenderMenjodohkan detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'isian_singkat'}
    <RenderIsian bind:jawaban disabled={disabled} placeholder="Tulis jawaban singkat..." />
{:else if tipe === 'uraian'}
    <RenderIsian bind:jawaban disabled={disabled} rows={4} placeholder="Tulis uraian jawaban..." />
{:else if tipe === 'mengurutkan'}
    <RenderMengurutkan detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'benar_salah' || tipe === 'setuju_tidak'}
    <RenderPernyataan detail={d} tipe={tipe} bind:jawaban disabled={disabled} />
{:else if tipe === 'matrix_text'}
    <RenderMatrix detail={d} mode="text" bind:jawaban disabled={disabled} />
{:else if tipe === 'matrix_checkbox'}
    <RenderMatrix detail={d} mode="checkbox" bind:jawaban disabled={disabled} />
{:else if tipe === 'mengelompokkan'}
    <RenderMengelompokkan detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'blank_space'}
    <RenderBlankSpace detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'image_labeling'}
    <RenderImageLabeling detail={d} bind:jawaban disabled={disabled} />
{:else if tipe === 'listening'}
    <RenderListening detail={d} bind:jawaban disabled={disabled} />
{:else}
    <div class="alert alert-warning">Tipe soal "{tipe}" belum didukung renderer.</div>
{/if}

{#if showKunci && d.kunci !== undefined}
    <div class="mt-2 p-2 bg-success bg-opacity-10 border border-success rounded small">
        <strong>Kunci:</strong>
        {#if Array.isArray(d.kunci)}
            {d.kunci.join(', ')}
        {:else if typeof d.kunci === 'object'}
            <pre class="mb-0">{JSON.stringify(d.kunci, null, 2)}</pre>
        {:else}
            {@html String(d.kunci)}
        {/if}
    </div>
{/if}
