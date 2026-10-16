<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Sesi = {
        id: number; nama: string; ujian_id: number;
        ujian_judul: string; ujian_kategori: string;
        kelas: string; matpel: string; ruang: string;
        waktu_mulai: string; waktu_selesai: string; tanggal: string;
        token_dibuka: boolean; jumlah_peserta: number;
    };

    let { sesis, kelasList, filters }: {
        sesis: { data: Sesi[]; links: any[] };
        kelasList: { id: number; nama: string }[];
        filters: { tanggal?: string; kelas_id?: string };
    } = $props();

    let tanggal = $state(filters.tanggal ?? '');
    let kelasId = $state(filters.kelas_id ?? '');

    function terapkanFilter() {
        router.get('/app/panitia/jadwal', {
            tanggal: tanggal || undefined,
            kelas_id: kelasId || undefined,
        }, { preserveState: true });
    }

    function resetFilter() {
        tanggal = ''; kelasId = '';
        router.get('/app/panitia/jadwal', {}, { preserveState: true });
    }

    function kategoriBadge(k: string) {
        const map: Record<string, string> = { uts: 'warning', uas: 'danger', usbk: 'info' };
        return map[k] ?? 'secondary';
    }
</script>

<svelte:head>
    <title>Jadwal Ujian — Panitia</title>
</svelte:head>

<PageHeader title="Jadwal Ujian" subtitle="Semua sesi ujian resmi lintas kelas">
    {#snippet actions()}
        <a use:inertia href="/app/panitia/jadwal/tambah" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Tambah Jadwal
        </a>
    {/snippet}
</PageHeader>

<Card class="mb-3">
    <CardBody>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="f-tanggal">Tanggal</label>
                <input id="f-tanggal" type="date" class="form-control" bind:value={tanggal} />
            </div>
            <div class="col-md-4">
                <label class="form-label" for="f-kelas">Kelas</label>
                <select id="f-kelas" class="form-select" bind:value={kelasId}>
                    <option value="">Semua kelas</option>
                    {#each kelasList as k}
                        <option value={k.id}>{k.nama}</option>
                    {/each}
                </select>
            </div>
            <div class="col-md-4">
                <Button color="primary" size="sm" onclick={terapkanFilter} class="me-2">
                    <i class="bi bi-funnel me-1"></i>Filter
                </Button>
                <Button color="outline-secondary" size="sm" onclick={resetFilter}>
                    Reset
                </Button>
            </div>
        </div>
    </CardBody>
</Card>

{#if sesis.data.length === 0}
    <EmptyState
        icon="bi-calendar-x"
        title="Belum ada jadwal"
        description="Buat ujian resmi dulu, lalu atur sesi di halaman detail ujian."
    />
{:else}
    <Card>
        <div class="table-responsive">
            <Table hover class="mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Ujian</th>
                        <th>Kelas</th>
                        <th>Sesi</th>
                        <th>Ruang</th>
                        <th>Peserta</th>
                        <th>Token</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each sesis.data as s}
                        <tr>
                            <td>
                                <div class="fw-semibold">{s.waktu_mulai}</div>
                                <div class="text-muted small">s/d {s.waktu_selesai}</div>
                            </td>
                            <td>
                                <div>{s.ujian_judul}</div>
                                <Badge color={kategoriBadge(s.ujian_kategori)} pill class="mt-1">
                                    {s.ujian_kategori.toUpperCase()}
                                </Badge>
                                <div class="text-muted small">{s.matpel}</div>
                            </td>
                            <td>{s.kelas}</td>
                            <td><Badge color="info" pill>{s.nama}</Badge></td>
                            <td>{s.ruang}</td>
                            <td class="text-center">{s.jumlah_peserta}</td>
                            <td>
                                {#if s.token_dibuka}
                                    <Badge color="success" pill><i class="bi bi-unlock-fill me-1"></i>Dibuka</Badge>
                                {:else}
                                    <Badge color="secondary" pill><i class="bi bi-lock-fill me-1"></i>Tutup</Badge>
                                {/if}
                            </td>
                            <td class="text-end">
                                <a use:inertia href="/app/panitia/ujian/{s.ujian_id}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-gear me-1"></i>Kelola
                                </a>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </div>
    </Card>
{/if}
