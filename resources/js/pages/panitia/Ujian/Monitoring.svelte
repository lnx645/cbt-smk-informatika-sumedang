<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Input, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { onMount, onDestroy } from 'svelte';

    type Pengerjaan = {
        id: number; nisn: string; nama: string; paket?: string; sesi?: string;
        status: string; mulai_at?: string; batas_at?: string;
        jumlah_pelanggaran: number; nilai_total?: number; is_remedial: boolean;
    };

    let {
        ujian, sesis, sesi_id, ringkasan, pengerjaans,
    }: {
        ujian: { id: number; judul: string; kategori: string };
        sesis: { id: number; nama: string }[];
        sesi_id?: number;
        ringkasan: { total: number; mengerjakan: number; selesai: number; pelanggaran: number };
        pengerjaans: Pengerjaan[];
    } = $props();

    let filterSesi = $state(sesi_id ? String(sesi_id) : '');
    let interval: ReturnType<typeof setInterval> | null = null;

    function refresh() {
        const url = filterSesi
            ? `/app/panitia/ujian/${ujian.id}/monitoring?sesi_id=${filterSesi}`
            : `/app/panitia/ujian/${ujian.id}/monitoring`;
        router.visit(url, { preserveScroll: true, preserveState: false });
    }

    onMount(() => {
        interval = setInterval(refresh, 15000);
    });
    onDestroy(() => {
        if (interval) clearInterval(interval);
    });

    function badgeStatus(s: string) {
        return s === 'berlangsung' ? 'primary'
            : s === 'selesai' || s === 'auto_submit' ? 'success'
            : s === 'diskualifikasi' ? 'danger' : 'secondary';
    }

    function resetPengerjaan(id: number, nama: string) {
        if (!confirm(`Reset pengerjaan ${nama}? Siswa bisa mulai ulang dari awal.`)) return;
        router.delete(`/app/panitia/ujian/${ujian.id}/pengerjaan/${id}/reset`, {
            preserveScroll: true,
        });
    }
</script>

<PageHeader title="Monitoring — {ujian.judul}" subtitle="Refresh otomatis tiap 15 detik" />

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <Card><CardBody class="text-center py-2">
            <div class="fs-4">{ringkasan.total}</div>
            <div class="text-muted small">Total Pengerjaan</div>
        </CardBody></Card>
    </div>
    <div class="col-6 col-md-3">
        <Card><CardBody class="text-center py-2">
            <div class="fs-4 text-primary">{ringkasan.mengerjakan}</div>
            <div class="text-muted small">Sedang Mengerjakan</div>
        </CardBody></Card>
    </div>
    <div class="col-6 col-md-3">
        <Card><CardBody class="text-center py-2">
            <div class="fs-4 text-success">{ringkasan.selesai}</div>
            <div class="text-muted small">Selesai</div>
        </CardBody></Card>
    </div>
    <div class="col-6 col-md-3">
        <Card><CardBody class="text-center py-2">
            <div class="fs-4 text-danger">{ringkasan.pelanggaran}</div>
            <div class="text-muted small">Kena Pelanggaran</div>
        </CardBody></Card>
    </div>
</div>

<Card>
    <CardHeader class="d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-semibold">Daftar Pengerjaan</span>
        {#if sesis.length > 0}
            <Input type="select" bsSize="sm" bind:value={filterSesi} onchange={refresh} style="width:auto">
                <option value="">Semua sesi</option>
                {#each sesis as s}<option value={s.id}>{s.nama}</option>{/each}
            </Input>
        {/if}
        <Button size="sm" color="secondary" outline onclick={refresh} class="ms-auto">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </Button>
    </CardHeader>
    <CardBody class="p-0">
        {#if pengerjaans.length === 0}
            <div class="p-3"><EmptyState message="Belum ada pengerjaan" /></div>
        {:else}
            <div class="table-responsive">
                <Table striped size="sm" class="mb-0">
                    <thead><tr>
                        <th>Siswa</th><th>Paket</th><th>Status</th>
                        <th>Pelanggaran</th><th>Nilai</th><th></th>
                    </tr></thead>
                    <tbody>
                        {#each pengerjaans as p}
                            <tr class={p.jumlah_pelanggaran > 0 ? 'table-warning' : ''}>
                                <td>
                                    <div class="fw-semibold">{p.nama}</div>
                                    <div class="text-muted small">{p.nisn}{p.is_remedial ? ' · remedial' : ''}</div>
                                </td>
                                <td>{#if p.paket}<Badge color="info">{p.paket}</Badge>{:else}–{/if}</td>
                                <td><Badge color={badgeStatus(p.status)}>{p.status}</Badge></td>
                                <td>
                                    {#if p.jumlah_pelanggaran > 0}
                                        <Badge color="danger">{p.jumlah_pelanggaran}x</Badge>
                                    {:else}–{/if}
                                </td>
                                <td>{p.nilai_total ?? '–'}</td>
                                <td>
                                    {#if p.status === 'berlangsung'}
                                        <Button size="sm" color="warning" outline
                                            onclick={() => resetPengerjaan(p.id, p.nama)}>
                                            Reset
                                        </Button>
                                    {/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </Table>
            </div>
        {/if}
    </CardBody>
</Card>
