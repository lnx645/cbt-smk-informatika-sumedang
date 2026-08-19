<script lang="ts">
    import { Card, CardBody, CardTitle, Button, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { router } from '@inertiajs/svelte';

    interface Kelas {
        id: number;
        nama: string;
    }

    interface SiswaItem {
        nisn: string;
        nama: string;
    }

    interface Props {
        penilaian: { id: number; nama: string };
        kelas: Kelas[];
        selectedKelasId: string | null;
        siswas: SiswaItem[] | null;
    }

    let { penilaian, kelas = [], selectedKelasId = null, siswas = null }: Props = $props();

    function changeKelas(event: Event) {
        const value = (event.currentTarget as HTMLSelectElement).value;
        const url = router.url({
            ...window.location,
            search: value ? `kelas_id=${value}` : '',
        });
        router.visit(url);
    }
</script>

<PageHeader
    title="Input Nilai"
    subtitle={`Penilaian: ${penilaian?.nama ?? ''}`}
/>

<Card class="mb-4">
    <CardBody>
        <CardTitle class="h6 fw-semibold mb-3">
            Pilih Kelas
        </CardTitle>
        <select
            class="form-select"
            value={selectedKelasId ?? ''}
            onchange={changeKelas}
        >
            <option value="">-- Pilih Kelas --</option>
            {#each kelas as k (k.id)}
                <option value={k.id}>{k.nama}</option>
            {/each}
        </select>

        {#if selectedKelasId}
            <p class="text-muted small mt-2 mb-0">
                Menampilkan siswa untuk kelas yang dipilih.
            </p>
        {/if}
    </CardBody>
</Card>

{#if selectedKelasId && siswas && siswas.length > 0}
    <Card>
        <CardBody>
            <CardTitle class="h6 fw-semibold mb-3">
                Siswa
            </CardTitle>
            <Table striped hover class="mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each siswas as s, i (s.nisn)}
                        <tr>
                            <td>{i + 1}</td>
                            <td>{s.nisn}</td>
                            <td>{s.nama}</td>
                            <td class="text-end">
                                <a
                                    href={DetailPenilaianController.detail({
                                        penilaian: penilaian.id,
                                        siswa: s.nisn,
                                    }).url}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Input Nilai
                                </a>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </CardBody>
    </Card>
{:else if selectedKelasId}
    <Card>
        <CardBody>
            <p class="text-muted mb-0">
                Tidak ada siswa ditemukan untuk kelas yang dipilih.
            </p>
        </CardBody>
    </Card>
{/if}

<div class="mt-4">
    <Button
        color="secondary"
        outline
        size="sm"
        onclick={() => router.visit(PenilaianController.index().url)}
    >
        <i class="bi bi-arrow-left me-1"></i>
        Kembali ke Daftar Penilaian
    </Button>
</div>

<style>
    .form-select {
        min-width: 200px;
    }
</style>
