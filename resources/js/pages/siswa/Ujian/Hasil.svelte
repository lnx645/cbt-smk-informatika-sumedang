<script lang="ts">
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { inertia } from '@inertiajs/svelte';
    import { KATEGORI_INFO, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    let {
        ujian,
        pengerjaan,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: KategoriUjian;
            nilai_maks: number;
            tampilkan_hasil: boolean;
        };
        pengerjaan: {
            status: string;
            nilai_objektif: number | null;
            nilai_esai: number | null;
            nilai_total: number | null;
            submitted_at: string | null;
            menunggu_koreksi: boolean;
        };
    } = $props();
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Daftar Ujian
        </a>
    </div>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-4 text-center">
            <Badge color={KATEGORI_INFO[ujian.kategori].color} pill class="mb-2">
                {KATEGORI_INFO[ujian.kategori].label}
            </Badge>
            <h1 class="h4 fw-semibold">{ujian.judul}</h1>
            <div class="text-muted small mb-4">
                Dikumpulkan {pengerjaan.submitted_at ?? '—'}
            </div>

            {#if pengerjaan.status === 'diskualifikasi'}
                <div class="alert alert-danger">
                    <i class="bi bi-x-octagon me-1"></i>
                    Ujian dihentikan karena melewati batas pelanggaran.
                </div>
            {/if}

            {#if !ujian.tampilkan_hasil}
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Hasil belum ditampilkan. Tunggu pengumuman dari guru.
                </div>
            {:else if pengerjaan.menunggu_koreksi}
                <div class="alert alert-warning">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Jawaban esai sedang menunggu koreksi guru. Nilai akhir belum final.
                </div>
                {#if pengerjaan.nilai_objektif !== null}
                    <div class="text-muted">
                        Nilai objektif sementara: {pengerjaan.nilai_objektif}
                    </div>
                {/if}
            {:else}
                <div class="display-4 fw-bold text-primary">
                    {pengerjaan.nilai_total ?? '—'}
                    <span class="fs-5 text-muted">/ {ujian.nilai_maks}</span>
                </div>
                <div class="text-muted small mt-2">
                    Objektif: {pengerjaan.nilai_objektif ?? 0} · Esai: {pengerjaan.nilai_esai ?? 0}
                </div>
            {/if}

            <div class="mt-4">
                <a use:inertia href={UjianController.index().url} class="btn btn-outline-primary">
                    Kembali ke Daftar Ujian
                </a>
            </div>
        </CardBody>
    </Card>
</div>
