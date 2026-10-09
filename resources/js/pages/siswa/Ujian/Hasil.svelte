<script lang="ts">
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
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

    const kembaliUrl = UjianController.index().url;
</script>

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + judul -->
    <div class="hasil-hero mb-4">
        <div class="hasil-hero__inner">
            <img class="hasil-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="hasil-hero__text">
                <span class="hasil-hero__eyebrow"
                    >Hasil {KATEGORI_INFO[ujian.kategori].label}</span
                >
                <h1 class="hasil-hero__title">{ujian.judul}</h1>
                <p class="hasil-hero__subtitle">
                    <i class="bi bi-check2-circle me-1"></i>Dikumpulkan{' '}
                    {pengerjaan.submitted_at ?? '—'}
                </p>
            </div>
        </div>
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
            <div class="hasil-meta text-center">
                Nilai objektif sementara: {pengerjaan.nilai_objektif}
            </div>
        {/if}
    {:else}
        <Card class="border rounded-1 shadow-none hasil-skor-card">
            <CardBody class="p-4 text-center">
                <div class="hasil-skor">
                    <div class="hasil-skor__nilai"
                        >{pengerjaan.nilai_total ?? '—'}</div
                    >
                    <div class="hasil-skor__maks">dari {ujian.nilai_maks}</div>
                </div>
                <div class="hasil-skor__rincian">
                    <span
                        ><i class="bi bi-patch-check me-1"></i
                        >Objektif: {pengerjaan.nilai_objektif ?? 0}</span
                    >
                    <span class="hasil-skor__dot">•</span>
                    <span
                        ><i class="bi bi-pencil-square me-1"></i
                        >Esai: {pengerjaan.nilai_esai ?? 0}</span
                    >
                </div>
            </CardBody>
        </Card>
    {/if}

    <div class="text-center mt-4">
        <a
            use:inertia
            href={kembaliUrl}
            class="btn btn-outline-primary"
            ><i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Ujian</a
        >
    </div>
</div>

<style>
    .hasil-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(124, 58, 237, 0.15);
    }

    .hasil-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .hasil-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.18);
        flex-shrink: 0;
    }

    .hasil-hero__text {
        min-width: 0;
    }

    .hasil-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .hasil-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
        word-break: break-word;
    }

    .hasil-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
    }

    .hasil-skor {
        padding: 0.75rem 0 0.35rem;
    }

    .hasil-skor__nilai {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.05;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .hasil-skor__maks {
        font-size: 0.95rem;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }

    .hasil-skor__rincian {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-top: 0.75rem;
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
    }

    .hasil-skor__dot {
        opacity: 0.5;
    }

    .hasil-skor-card {
        background: transparent;
    }

    .hasil-meta {
        margin-top: 0.5rem;
        color: var(--bs-secondary-color);
    }

    @media (max-width: 575.98px) {
        .hasil-hero {
            padding: 1.1rem 1.1rem;
        }
        .hasil-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .hasil-hero__title {
            font-size: 1.25rem;
        }
    }
</style>