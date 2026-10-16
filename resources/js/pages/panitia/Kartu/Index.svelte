<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Card, CardBody, CardHeader, Button, Table } from '@sveltestrap/sveltestrap';

    let { kelass = [], statistik = { total: 0, sudah: 0, belum: 0 } } = $props<{
        kelass: { id: number; nama: string; jumlah_siswa: number; sudah_password: number }[];
        statistik: { total: number; sudah: number; belum: number };
    }>();

    const generateForm = useForm({});
    let kelasTerpilih = $state<number | null>(null);

    function generate(kelasId?: number) {
        const url = kelasId
            ? `/app/panitia/kartu/generate/${kelasId}`
            : '/app/panitia/kartu/generate';
        generateForm.post(url, { preserveScroll: true });
    }
</script>

<div class="container-fluid py-3">
    <h4 class="mb-3"><i class="bi bi-person-badge me-2"></i>Kartu Ujian</h4>

    <div class="row mb-3">
        <div class="col-md-4">
            <Card class="text-bg-primary">
                <CardBody>
                    <div class="fs-2 fw-bold">{statistik.total}</div>
                    <div>Total Siswa</div>
                </CardBody>
            </Card>
        </div>
        <div class="col-md-4">
            <Card class="text-bg-success">
                <CardBody>
                    <div class="fs-2 fw-bold">{statistik.sudah}</div>
                    <div>Sudah Punya Password</div>
                </CardBody>
            </Card>
        </div>
        <div class="col-md-4">
            <Card class="text-bg-warning">
                <CardBody>
                    <div class="fs-2 fw-bold">{statistik.belum}</div>
                    <div>Belum Punya Password</div>
                </CardBody>
            </Card>
        </div>
    </div>

    <Card class="mb-3">
        <CardHeader>
            <i class="bi bi-key me-1"></i>Generate Password
            <small class="text-muted ms-2">— satu password berlaku untuk semua ujian</small>
        </CardHeader>
        <CardBody>
            <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
                <Button color="primary" onclick={() => generate()} disabled={generateForm.processing}>
                    <i class="bi bi-lightning me-1"></i>Generate Semua ({statistik.belum} siswa)
                </Button>
                <span class="text-muted small">atau pilih kelas:</span>
            </div>
            <Table striped hover responsive size="sm">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th class="text-center">Jumlah Siswa</th>
                        <th class="text-center">Sudah Ada Password</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each kelass as k}
                        <tr>
                            <td><strong>{k.nama}</strong></td>
                            <td class="text-center">{k.jumlah_siswa}</td>
                            <td class="text-center">
                                <span class="badge {k.sudah_password === k.jumlah_siswa ? 'text-bg-success' : 'text-bg-warning'}">
                                    {k.sudah_password}/{k.jumlah_siswa}
                                </span>
                            </td>
                            <td class="text-end">
                                <Button size="sm" color="outline-primary" onclick={() => generate(k.id)} disabled={generateForm.processing || k.sudah_password === k.jumlah_siswa}>
                                    <i class="bi bi-key me-1"></i>Generate
                                </Button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </CardBody>
    </Card>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Setelah password di-generate, siswa bisa download kartu ujiannya sendiri dari dashboard.
        Password bersifat universal — berlaku untuk semua UTS/UAS/USBK.
    </div>
</div>
