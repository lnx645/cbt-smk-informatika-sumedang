<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import MatpelGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';

    type MateriBaru = {
        id: number;
        judul: string;
        matpel: string | null;
        guru: string | null;
        dibuat_pada: string;
    };

    type RingkasanMatpel = {
        id: number | null;
        matpel: string;
        total: number;
    };

    type Kutipan = {
        teks: string;
        penulis: string;
    };

    let {
        nama = null,
        kelas = null,
        tahunAjaran = null,
        kutipan = null,
        materiTerbaru = [],
        ringkasan = [],
    }: {
        nama: string | null;
        kelas: string | null;
        tahunAjaran: string | null;
        kutipan: Kutipan | null;
        materiTerbaru: MateriBaru[];
        ringkasan: RingkasanMatpel[];
    } = $props();

    const materiUrl = MateriController.index().url;

    const totalMateri = ringkasan.reduce((jumlah, item) => jumlah + item.total, 0);
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Dashboard"
        subtitle={nama
            ? `Halo, ${nama}!${kelas ? ` Kamu di kelas ${kelas}` : ''}${tahunAjaran ? ` · Tahun Ajaran ${tahunAjaran}` : ''}.`
            : 'Selamat datang di aplikasi sekolah.'}
    />

    {#if kutipan}
        <Card class="border rounded-1 shadow-sm mb-4">
            <CardBody class="p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-quote text-primary" style="font-size: 1.75rem"></i>
                    <div class="min-w-0">
                        <p class="mb-1 fst-italic">"{kutipan.teks}"</p>
                        <span class="text-muted small fw-semibold">— {kutipan.penulis}</span>
                    </div>
                </div>
            </CardBody>
        </Card>
    {/if}

    {#if ringkasan.length > 0}
        <div class="d-flex gap-2 flex-wrap mb-3">
            <span class="stat-chip">
                <i class="bi bi-journal-bookmark me-1 text-primary"></i>
                {ringkasan.length} Mata Pelajaran
            </span>
            <span class="stat-chip">
                <i class="bi bi-files me-1 text-primary"></i>
                {totalMateri} Materi
            </span>
        </div>

        <h2 class="h6 fw-semibold mb-3">
            <i class="bi bi-grid-1x2 me-1 text-primary"></i>
            Materi per Mata Pelajaran
        </h2>
        <div class="row g-3 mb-4">
            {#each ringkasan as item (item.id)}
                <div class="col-6 col-md-4 col-lg-3">
                    {#if item.id}
                        <a
                            use:inertia
                            href={`${materiUrl}?matpel=${item.id}`}
                            class="text-decoration-none d-block"
                        >
                            <Card class="border rounded-1 shadow-sm h-100">
                                <CardBody class="p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-journal-bookmark text-primary" style="font-size: 1.4rem"></i>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{item.matpel}</div>
                                            <div class="text-muted small">{item.total} materi</div>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        </a>
                    {:else}
                        <Card class="border rounded-1 shadow-sm h-100">
                            <CardBody class="p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-journal-bookmark text-primary" style="font-size: 1.4rem"></i>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{item.matpel}</div>
                                        <div class="text-muted small">{item.total} materi</div>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}

    <h2 class="h6 fw-semibold mb-3">
        <i class="bi bi-clock-history me-1 text-primary"></i>
        Materi Terbaru
    </h2>

    {#if materiTerbaru.length > 0}
        <div class="row g-3">
            {#each materiTerbaru as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-sm h-100">
                        <CardBody class="d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <i class="bi bi-file-earmark-richtext text-primary" style="font-size: 1.6rem"></i>
                                <Badge color="primary">{item.matpel ?? '—'}</Badge>
                            </div>
                            <h3 class="h6 fw-semibold mb-1">{item.judul}</h3>
                            <div class="flex-grow-1"></div>
                            <div class="text-muted small mb-3">
                                <div>
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                                </div>
                                <div>
                                    <i class="bi bi-calendar3 me-1"></i>{item.dibuat_pada}
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a
                                    use:inertia
                                    href={MateriController.show({ materi: item.id }).url}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
        <div class="mt-3">
            <a use:inertia href={materiUrl} class="btn btn-sm btn-outline-primary">
                <i class="bi bi-collection me-1"></i>Lihat Semua Materi
            </a>
        </div>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-inbox" style="font-size: 2.5rem"></i>
                    <p class="mt-3 mb-0">Belum ada materi untuk kelasmu.</p>
                </div>
            </CardBody>
        </Card>
    {/if}

    {#if !kutipan}
        <Card class="border rounded-1 shadow-sm mt-4">
            <CardBody class="p-3 p-md-4">
                <h2 class="h6 fw-semibold mb-1">Selamat bekerja, {nama ?? 'Guru'}!</h2>
                <p class="text-muted small mb-3">Kelola materi pembelajaran untuk kelas yang kamu ajar.</p>
                <div class="d-flex gap-2">
                    <a use:inertia href={GuruMateriController.index().url} class="btn btn-sm btn-primary">
                        <i class="bi bi-files me-1"></i>Kelola Materi
                    </a>
                    <a use:inertia href={MatpelGuruController.index().url} class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-journal-bookmark me-1"></i>Matpel Saya
                    </a>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>

<style>
    .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        background: var(--bs-primary-bg-subtle);
        font-size: var(--bs-font-size-sm);
        font-weight: 600;
        color: var(--bs-primary-700, var(--bs-body-color));
    }
</style>