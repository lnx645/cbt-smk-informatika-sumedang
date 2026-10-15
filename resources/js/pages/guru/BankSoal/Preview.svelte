<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import SoalRenderer from '@/components/soal/render/SoalRenderer.svelte';

    type Soal = {
        id: number; tipe: string; tipe_label: string; pertanyaan: string;
        detail: Record<string, any> | null;
    };

    let { kelompok, grouped }: {
        kelompok: { id: number; nama: string; kode?: string | null; matpel?: string };
        grouped: { tipe: string; tipe_label: string; soals: Soal[] }[];
    } = $props();

    let showKunci = $state(false);
    let filterTipe = $state('semua');
    let expanded = $state<Set<number>>(new Set());

    function toggle(id: number) {
        const s = new Set(expanded);
        if (s.has(id)) s.delete(id); else s.add(id);
        expanded = s;
    }
    function expandAll(soals: { id: number }[]) {
        expanded = new Set(soals.map(s => s.id));
    }
    function collapseAll() {
        expanded = new Set();
    }

    const filtered = $derived(
        filterTipe === 'semua' ? grouped : grouped.filter(g => g.tipe === filterTipe)
    );
    const totalSoal = $derived(grouped.reduce((s, g) => s + g.soals.length, 0));
</script>

<svelte:head>
    <title>Preview — {kelompok.nama}</title>
</svelte:head>

<PageHeader title="Preview Bank Soal" subtitle="{kelompok.nama} · {totalSoal} soal">
    {#snippet actions()}
        <Button size="sm" color={showKunci ? 'warning' : 'outline-warning'} onclick={() => (showKunci = !showKunci)}>
            <i class="bi bi-key me-1"></i>{showKunci ? 'Sembunyikan' : 'Tampilkan'} Kunci
        </Button>
        <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    {/snippet}
</PageHeader>

{#if grouped.length === 0}
    <EmptyState message="Belum ada soal" hint="Tambah soal dulu untuk melihat preview." />
{:else}
    <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
        <label class="form-label mb-0 small fw-semibold" for="filter">Filter tipe:</label>
        <select id="filter" class="form-select form-select-sm" style="max-width:250px" bind:value={filterTipe}>
            <option value="semua">Semua tipe ({totalSoal})</option>
            {#each grouped as g}<option value={g.tipe}>{g.tipe_label} ({g.soals.length})</option>{/each}
        </select>
    </div>

    {#each filtered as group}
        <Card class="mb-4">
            <CardHeader class="d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-tag me-1"></i>{group.tipe_label}</span>
                <Badge color="primary">{group.soals.length} soal</Badge>
            </CardHeader>
            <CardBody>
                <div class="mb-2 d-flex gap-2">
                    <Button size="sm" color="link" class="p-0" onclick={() => expandAll(group.soals)}>Buka semua</Button>
                    <span class="text-muted">·</span>
                    <Button size="sm" color="link" class="p-0" onclick={collapseAll}>Tutup semua</Button>
                </div>
                {#each group.soals as soal, i}
                    <div class="border rounded mb-2 bg-white">
                        <div class="p-3 d-flex justify-content-between align-items-center gap-2"
                            style="cursor:pointer" onclick={() => toggle(soal.id)} role="button" tabindex="0"
                            onkeydown={(e) => { if (e.key === 'Enter') toggle(soal.id); }}>
                            <div class="d-flex gap-2 align-items-center flex-grow-1" style="min-width:0">
                                <i class="bi {expanded.has(soal.id) ? 'bi-chevron-down' : 'bi-chevron-right'} text-muted flex-shrink-0"></i>
                                <span class="badge bg-secondary flex-shrink-0">Soal {i + 1}</span>
                                <span class="text-truncate flex-grow-1">{(soal.pertanyaan ?? '').replace(/<[^>]*>/g, '').substring(0, 80)}</span>
                            </div>
                            <a use:inertia href="/app/guru/bank-soal/kelompok/{kelompok.id}/soal/{soal.id}/edit"
                                class="btn btn-sm btn-link p-0 flex-shrink-0" title="Edit soal" onclick={(e) => e.stopPropagation()}>
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                        {#if expanded.has(soal.id)}
                            <div class="p-3 pt-0 border-top">
                                <div class="mb-3 mt-2">{@html soal.pertanyaan}</div>
                                <SoalRenderer tipe={soal.tipe} detail={soal.detail} disabled={false} showKunci={showKunci} />
                            </div>
                        {/if}
                    </div>
                {/each}
            </CardBody>
        </Card>
    {/each}
{/if}
