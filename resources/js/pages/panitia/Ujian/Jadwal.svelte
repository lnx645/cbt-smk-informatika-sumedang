<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Jadwal = {
        id: number; nama: string; event: string;
        banksoal: string; matpel: string;
        tanggal: string; durasi: number;
        jumlah_sesi: number; status: string;
    };

    let { jadwals, filters }: {
        jadwals: { data: Jadwal[]; links: any[] };
        filters: { tanggal?: string };
    } = $props();

    let tanggal = $state(filters.tanggal ?? '');

    function terapkanFilter() {
        router.get('/app/panitia/jadwal', {
            tanggal: tanggal || undefined,
        }, { preserveState: true });
    }

    function resetFilter() {
        tanggal = '';
        router.get('/app/panitia/jadwal', {}, { preserveState: true });
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

{#if jadwals.data.length === 0}
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
                        <th>Nama Jadwal</th>
                        <th>Event</th>
                        <th>Bank Soal</th>
                        <th>Tanggal</th>
                        <th>Sesi</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each jadwals.data as j}
                        <tr>
                            <td>
                                <div class="fw-semibold">{j.nama}</div>
                                <div class="text-muted small">{j.matpel} &middot; {j.durasi} menit</div>
                            </td>
                            <td>{j.event}</td>
                            <td>{j.banksoal}</td>
                            <td>{j.tanggal}</td>
                            <td><Badge color="info" pill>{j.jumlah_sesi} sesi</Badge></td>
                            <td><Badge color="secondary" pill>{j.status}</Badge></td>
                            <td class="text-end">
                                <a use:inertia href="/app/panitia/jadwal/{j.id}" class="btn btn-sm btn-outline-primary">
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
