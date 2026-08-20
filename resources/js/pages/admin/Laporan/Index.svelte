<script lang="ts">
    import PageHeader from '@/components/PageHeader.svelte';
    import {
        Card,
        CardBody,
        CardTitle,
        Table,
    } from '@sveltestrap/sveltestrap';
    import LaporanController from '@/actions/App/Http/Controllers/Admin/LaporanController';

    interface Props {
        counts: Record<string, number>;
    }

    let { counts }: Props = $props();

    const items = $derived(
        [
            {
                label: 'Jurusan',
                key: 'jurusan',
                icon: 'bi-diagram-3-fill',
            },
            {
                label: 'Mata Pelajaran',
                key: 'matpel',
                icon: 'bi-book-half',
            },
            {
                label: 'Tahun Ajaran',
                key: 'tahunAjaran',
                icon: 'bi-calendar',
            },
            {
                label: 'Kelas',
                key: 'kelas',
                icon: 'bi-collection-fill',
            },
            { label: 'Guru', key: 'guru', icon: 'bi-people-fill' },
            {
                label: 'Siswa',
                key: 'siswa',
                icon: 'bi-mortarboard-fill',
            },
            {
                label: 'Penugasan Guru-Kelas',
                key: 'penugasan',
                icon: 'bi-person-workspace',
            },
            {
                label: 'Materi',
                key: 'materi',
                icon: 'bi-file-earmark-richtext',
            },
            { label: 'Tugas', key: 'tugas', icon: 'bi-list-check' },
            {
                label: 'Pengumpulan Tugas',
                key: 'pengumpulan',
                icon: 'bi-inbox',
            },
            {
                label: 'Penilaian',
                key: 'penilaian',
                icon: 'bi-card-checklist',
            },
            {
                label: 'Kelas Penilaian',
                key: 'penilaianKelas',
                icon: 'bi-layers',
            },
            {
                label: 'Detail Nilai',
                key: 'detailNilai',
                icon: 'bi-clipboard-data',
            },
            {
                label: 'Riwayat Kelas Siswa',
                key: 'siswaKelas',
                icon: 'bi-arrow-left-right',
            },
            {
                label: 'Akun Pengguna',
                key: 'users',
                icon: 'bi-person-badge',
            },
        ].map((item) => ({ ...item, count: counts[item.key] ?? 0 })),
    );

    const total = $derived(
        items.reduce((sum, item) => sum + item.count, 0),
    );

    const xlsxUrl = LaporanController.exportXlsx().url;
</script>

<PageHeader
    title="Laporan"
    subtitle="Cetak seluruh data aplikasi dalam satu file XLSX (per sheet) atau PDF."
/>

<Card>
    <CardBody>
        <CardTitle class="mb-3">Unduh Laporan</CardTitle>
        <div class="d-flex flex-wrap gap-2 mb-1">
            <a class="btn btn-success" href={xlsxUrl}>
                <i class="bi bi-file-earmark-excel me-1"></i>Unduh
                XLSX
            </a>
        </div>
        <p class="text-muted small mb-0">
            Total {total} baris data dari {items.length} entitas, dicetak
            sesuai data terbaru saat tombol ditekan.
        </p>
    </CardBody>
</Card>

<Card class="mt-3">
    <CardBody>
        <CardTitle class="mb-3">Isi Laporan</CardTitle>
        <Table responsive size="sm" hover striped>
            <thead>
                <tr>
                    <th>Entitas</th>
                    <th class="text-end">Jumlah Data</th>
                </tr>
            </thead>
            <tbody>
                {#each items as item}
                    <tr>
                        <td>
                            <i
                                class="bi {item.icon} me-2 text-primary"
                            ></i>
                            {item.label}
                        </td>
                        <td class="text-end">{item.count}</td>
                    </tr>
                {/each}
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="text-end">{total}</th>
                </tr>
            </tfoot>
        </Table>
    </CardBody>
</Card>
