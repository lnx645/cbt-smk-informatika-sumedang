<script lang="ts">
    import { Badge, Button, Card, CardBody, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import { inertia } from '@inertiajs/svelte';

    type Sesi = {
        id: number; nama: string;
        jam_mulai: string; jam_selesai: string;
        ruang: string; kapasitas: number; terisi: number;
    };

    type Jadwal = {
        id: number; nama: string; event: string;
        banksoal: string; matpel: string;
        tanggal: string; durasi: number;
        mode: string; status: string; kelas: string;
        sesi: Sesi[];
    };

    let { jadwal }: { jadwal: Jadwal } = $props();

    import { router } from '@inertiajs/svelte';

    function terbitkan() {
        if (confirm('Terbitkan jadwal ini?')) {
            router.post(`/app/panitia/jadwal/${jadwal.id}/terbitkan`);
        }
    }

    function buatToken(sesiId: number) {
        router.post(`/app/panitia/jadwal-sesi/${sesiId}/token`);
    }
</script>

<svelte:head>
    <title>{jadwal.nama} — Detail Jadwal</title>
</svelte:head>

<PageHeader title={jadwal.nama} subtitle="Detail jadwal ujian">
    {#snippet actions()}
        {#if jadwal.status === 'draft'}
            <Button color="success" size="sm" class="me-2" onclick={terbitkan}>
                <i class="bi bi-check-circle me-1"></i>Terbitkan
            </Button>
        {/if}
        <a use:inertia href="/app/panitia/jadwal/{jadwal.id}/edit" class="btn btn-sm btn-outline-primary me-2">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a use:inertia href="/app/panitia/jadwal" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    {/snippet}
</PageHeader>

<Card class="mb-3">
    <CardBody>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Event:</strong> {jadwal.event}</p>
                <p><strong>Bank Soal:</strong> {jadwal.banksoal}</p>
                <p><strong>Mapel:</strong> {jadwal.matpel}</p>
                <p><strong>Kelas:</strong> {jadwal.kelas || '-'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Tanggal:</strong> {jadwal.tanggal}</p>
                <p><strong>Durasi:</strong> {jadwal.durasi} menit</p>
                <p><strong>Mode:</strong> <Badge color={jadwal.mode === 'luring' ? 'primary' : 'info'} pill>{jadwal.mode.toUpperCase()}</Badge></p>
                <p><strong>Status:</strong> <Badge color="secondary" pill>{jadwal.status}</Badge></p>
            </div>
        </div>
    </CardBody>
</Card>

<Card>
    <CardBody>
        <h5 class="mb-3">Sesi Ujian ({jadwal.sesi.length})</h5>
        <div class="table-responsive">
            <Table hover class="mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sesi</th>
                        <th>Jam</th>
                        <th>Ruang</th>
                        <th>Kapasitas</th>
                        <th>Terisi</th>
                        <th>Token</th>
                    </tr>
                </thead>
                <tbody>
                    {#each jadwal.sesi as s}
                        <tr>
                            <td><Badge color="info" pill>{s.nama}</Badge></td>
                            <td>{s.jam_mulai} - {s.jam_selesai}</td>
                            <td>{s.ruang}</td>
                            <td class="text-center">{s.kapasitas}</td>
                            <td class="text-center">{s.terisi}</td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </div>
    </CardBody>
</Card>
