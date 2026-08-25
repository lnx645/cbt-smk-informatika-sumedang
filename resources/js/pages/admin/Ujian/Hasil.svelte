<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { KATEGORI_INFO, STATUS_PENGERJAAN_INFO, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Admin/UjianController';

    type SiswaHasil = {
        nisn: string;
        nama: string;
        pengerjaan_id: number | null;
        status: string;
        nilai_total: number | null;
        nilai_objektif: number | null;
        nilai_esai: number | null;
        jumlah_pelanggaran: number;
        submitted_at: string | null;
    };

    let {
        ujian,
        siswas,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: KategoriUjian;
            nilai_maks: number;
            guru: string | null;
            kelas: string | null;
            matpel: string | null;
            jumlah_soal: number;
            ada_esai: boolean;
        };
        siswas: SiswaHasil[];
    } = $props();
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Monitor Ujian
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">
                {ujian.judul}
                <Badge color={KATEGORI_INFO[ujian.kategori].color} pill class="ms-1">
                    {KATEGORI_INFO[ujian.kategori].label}
                </Badge>
            </h1>
            <div class="text-muted small">
                {ujian.guru} · {ujian.kelas} · {ujian.matpel} · {ujian.jumlah_soal} soal
            </div>
        </div>
        {#if ujian.ada_esai}
            <Badge color="warning" pill>Ada soal esai</Badge>
        {/if}
    </div>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Objektif</th>
                            <th>Esai</th>
                            <th>Nilai</th>
                            <th>Pelanggaran</th>
                            <th>Dikumpulkan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each siswas as s (s.nisn)}
                            <tr>
                                <td>
                                    <div class="fw-semibold">{s.nama}</div>
                                    <div class="text-muted small">{s.nisn}</div>
                                </td>
                                <td>
                                    <Badge color={STATUS_PENGERJAAN_INFO[s.status]?.color ?? 'secondary'} pill>
                                        {STATUS_PENGERJAAN_INFO[s.status]?.label ?? s.status}
                                    </Badge>
                                </td>
                                <td>{s.nilai_objektif ?? '—'}</td>
                                <td>{s.nilai_esai ?? '—'}</td>
                                <td class="fw-semibold">{s.nilai_total ?? '—'}</td>
                                <td>
                                    {#if s.jumlah_pelanggaran > 0}
                                        <Badge color="danger" pill>{s.jumlah_pelanggaran}</Badge>
                                    {:else}
                                        <span class="text-muted">0</span>
                                    {/if}
                                </td>
                                <td class="small text-muted">{s.submitted_at ?? '—'}</td>
                                <td class="text-end">
                                    {#if s.pengerjaan_id}
                                        <a
                                            use:inertia
                                            href={UjianController.hasilShow({ ujian: ujian.id, pengerjaan: s.pengerjaan_id }).url}
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    {:else}
                                        <span class="text-muted small">Belum mengerjakan</span>
                                    {/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </CardBody>
    </Card>
</div>
