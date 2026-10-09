<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { KATEGORI_INFO, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type UjianItem = {
        id: number;
        judul: string;
        kategori: KategoriUjian;
        matpel: string | null;
        guru: string | null;
        durasi_menit: number;
        jumlah_soal: number;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        sedang_berlangsung: boolean;
        butuh_token: boolean;
        attempt_terpakai: number;
        maks_attempt: number;
        sedang_dikerjakan: boolean;
        tampilkan_hasil: boolean;
        nilai_total: number | null;
    };

    let { ujians }: { ujians: UjianItem[] } = $props();

    const tokenForm = useForm({ token: '' });
    let tokenUntuk = $state<number | null>(null);

    // Warna solid per kategori ujian untuk avatar kartu.
    const KATEGORI_AVATAR: Record<KategoriUjian, string> = {
        kuis: 'info',
        uts: 'primary',
        uas: 'warning',
        usbk: 'danger',
    };

    function mulai(item: UjianItem) {
        if (item.butuh_token) {
            tokenUntuk = tokenUntuk === item.id ? null : item.id;
            return;
        }
        router.post(UjianController.mulai({ ujian: item.id }).url);
    }

    function submitToken(item: UjianItem) {
        tokenForm.post(UjianController.mulai({ ujian: item.id }).url, {
            onSuccess: () => tokenForm.reset(),
        });
    }
</script>

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + judul -->
    <div class="ujian-hero mb-4">
        <div class="ujian-hero__inner">
            <img class="ujian-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="ujian-hero__text">
                <span class="ujian-hero__eyebrow">Evaluasi</span>
                <h1 class="ujian-hero__title">Ujian & Kuis</h1>
                <p class="ujian-hero__subtitle">
                    Kerjakan kuis dan ujian dari guru sesuai jadwal.
                </p>
            </div>
        </div>
    </div>

    {#if ujians.length === 0}
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal-x display-5 d-block mb-2"></i>
            <div>Belum ada ujian untuk kelasmu saat ini.</div>
        </div>
    {:else}
        <div class="row g-3">
            {#each ujians as item (item.id)}
                <div class="col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-none h-100 ujian-card">
                        <CardBody class="p-3 d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div class="ujian-avatar ujian-avatar--{KATEGORI_AVATAR[item.kategori]}">
                                    <i class="bi bi-journal-text"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <Badge color={KATEGORI_INFO[item.kategori].color} pill>
                                            {KATEGORI_INFO[item.kategori].label}
                                        </Badge>
                                        {#if item.butuh_token}
                                            <Badge color="secondary" pill class="flex-shrink-0">
                                                <i class="bi bi-key me-1"></i>Token
                                            </Badge>
                                        {/if}
                                    </div>
                                    <h2 class="h6 fw-semibold mb-0 mt-1 text-truncate">{item.judul}</h2>
                                </div>
                            </div>
                            <div class="text-muted small mb-2">
                                <i class="bi bi-journal-bookmark me-1"></i>{item.matpel ?? 'Matpel'}
                                <span class="mx-1">·</span>
                                <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                            </div>
                            <div class="ujian-meta">
                                <span><i class="bi bi-clock me-1"></i>{item.durasi_menit} menit</span>
                                <span><i class="bi bi-patch-question me-1"></i>{item.jumlah_soal} soal</span>
                            </div>
                            <div class="small text-muted mt-2 mb-3">
                                <i class="bi bi-calendar3 me-1"></i>{item.tanggal_mulai ?? 'Kapan saja'}
                                {#if item.tanggal_selesai}
                                    – {item.tanggal_selesai}
                                {/if}
                            </div>

                            <div class="mt-auto">
                                {#if item.sedang_dikerjakan}
                                    <a
                                        use:inertia
                                        href={UjianController.kerjakan({ ujian: item.id }).url}
                                        class="btn btn-warning w-100"
                                    >
                                        <i class="bi bi-play-fill me-1"></i>Lanjutkan
                                    </a>
                                {:else if item.attempt_terpakai >= item.maks_attempt}
                                    <div class="d-grid gap-2">
                                        <Button color="secondary" disabled>
                                            Kesempatan habis
                                        </Button>
                                        {#if item.tampilkan_hasil}
                                            <a
                                                use:inertia
                                                href={UjianController.hasil({ ujian: item.id }).url}
                                                class="btn btn-outline-info btn-sm"
                                            >
                                                Lihat Hasil{item.nilai_total !== null
                                                    ? ` (${item.nilai_total})`
                                                    : ''}
                                            </a>
                                        {/if}
                                    </div>
                                {:else if !item.sedang_berlangsung}
                                    <Button color="secondary" class="w-100" disabled>
                                        Belum dibuka / sudah berakhir
                                    </Button>
                                {:else if tokenUntuk === item.id}
                                    <div class="input-group">
                                        <input
                                            class="form-control text-uppercase"
                                            placeholder="Token"
                                            bind:value={tokenForm.token}
                                        />
                                        <Button color="primary" onclick={() => submitToken(item)}>
                                            Mulai
                                        </Button>
                                    </div>
                                    {#if tokenForm.errors.token}
                                        <div class="text-danger small mt-1">
                                            {tokenForm.errors.token}
                                        </div>
                                    {/if}
                                {:else}
                                    <Button color="primary" class="w-100" onclick={() => mulai(item)}>
                                        <i class="bi bi-play-fill me-1"></i>Mulai
                                    </Button>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {/if}
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .ujian-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(124, 58, 237, 0.15);
    }

    .ujian-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .ujian-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .ujian-hero__text {
        min-width: 0;
    }

    .ujian-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .ujian-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
    }

    .ujian-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
    }

    /* ---------- Kartu ujian ---------- */
    .ujian-card {
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .ujian-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }

    .ujian-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 0.8rem;
        font-size: 1.35rem;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .ujian-avatar--info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .ujian-avatar--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .ujian-avatar--warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .ujian-avatar--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .ujian-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem 1rem;
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
    }

    @media (max-width: 575.98px) {
        .ujian-hero {
            padding: 1.1rem 1.1rem;
        }
        .ujian-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .ujian-hero__title {
            font-size: 1.25rem;
        }
    }
</style>
