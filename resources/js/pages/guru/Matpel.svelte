<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import SiswaMateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';

    type KelasPenugasan = {
        guru_kelas_id: number;
        nama: string;
    };

    type MatpelItem = {
        id: number;
        name: string;
        description: string | null;
        kelas?: KelasPenugasan[];
        guru?: string | null;
        total_materi: number;
    };

    let {
        role = 'guru',
        tahunAjaran = null,
        kelas = null,
        matpels = [],
    }: {
        role: string;
        tahunAjaran: string | null;
        kelas: string | null;
        matpels: MatpelItem[];
    } = $props();

    const isGuru = role === 'guru';

    const guruMateriUrl = GuruMateriController.index().url;
    const siswaMateriUrl = SiswaMateriController.index().url;

    const totalKelas = $derived(
        matpels.reduce((jumlah, item) => jumlah + (item.kelas?.length ?? 0), 0),
    );
    const totalMateri = $derived(
        matpels.reduce((jumlah, item) => jumlah + item.total_materi, 0),
    );

    function stripHtml(html: string | null): string {
        if (!html) return '';
        return html
            .replace(/&nbsp;/gi, ' ')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#0*39;/g, "'")
            .replace(/&amp;/g, '&')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title={isGuru ? 'Matpel Saya' : 'Mata Pelajaran'}
        subtitle={isGuru
            ? `Mata pelajaran yang kamu ajar pada tahun ajaran ${tahunAjaran ?? 'aktif'}.`
            : `Mata pelajaran kelas ${kelas ?? 'kamu'} pada tahun ajaran ${tahunAjaran ?? 'aktif'}.`}
    />

    {#if matpels.length > 0}
        <div class="d-flex gap-2 flex-wrap mb-3">
            <span class="stat-chip">
                <i class="bi bi-journal-bookmark me-1 text-primary"></i>
                {matpels.length} Mata Pelajaran
            </span>
            {#if isGuru}
                <span class="stat-chip">
                    <i class="bi bi-people me-1 text-primary"></i>
                    {totalKelas} Kelas
                </span>
            {/if}
            <span class="stat-chip">
                <i class="bi bi-files me-1 text-primary"></i>
                {totalMateri} Materi
            </span>
        </div>

        <div class="row g-3">
            {#each matpels as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-sm h-100">
                        <CardBody class="d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="bi bi-journal-bookmark text-primary" style="font-size: 1.5rem"></i>
                                    <h3 class="h6 fw-semibold mb-0 text-truncate">{item.name}</h3>
                                </div>
                                <Badge color="primary" class="flex-shrink-0">{item.total_materi} materi</Badge>
                            </div>

                            {#if item.description}
                                <div class="text-muted small matpel-deskripsi-snippet mb-3" title={stripHtml(item.description)}>
                                    {stripHtml(item.description)}
                                </div>
                            {:else}
                                <div class="flex-grow-1"></div>
                            {/if}

                            {#if isGuru && item.kelas?.length}
                                <div class="mb-3">
                                    <div class="text-muted small fw-semibold mb-1">Kelas yang diajar</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        {#each item.kelas as k (k.guru_kelas_id)}
                                            <a
                                                use:inertia
                                                href={`${guruMateriUrl}?guru_kelas_id=${k.guru_kelas_id}`}
                                                class="badge text-bg-light border text-decoration-none text-body"
                                            >
                                                <i class="bi bi-people me-1"></i>{k.nama}
                                            </a>
                                        {/each}
                                    </div>
                                </div>
                            {:else if !isGuru}
                                <div class="text-muted small mb-3">
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru pengampu'}
                                </div>
                            {:else}
                                <div class="flex-grow-1"></div>
                            {/if}

                            <div class="mt-auto">
                                {#if isGuru}
                                    <a use:inertia href={guruMateriUrl} class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-files me-1"></i>Kelola Materi
                                    </a>
                                {:else}
                                    <a
                                        use:inertia
                                        href={`${siswaMateriUrl}?matpel=${item.id}`}
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="bi bi-eye me-1"></i>Lihat Materi
                                    </a>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-journal-bookmark" style="font-size: 3rem"></i>
                    <p class="mt-3 mb-0">
                        {isGuru
                            ? 'Kamu belum memiliki penugasan kelas aktif pada tahun ajaran ini. Hubungi admin untuk menambahkan penugasan.'
                            : 'Belum ada mata pelajaran untuk kelasmu pada tahun ajaran ini.'}
                    </p>
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

    .matpel-deskripsi-snippet {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>