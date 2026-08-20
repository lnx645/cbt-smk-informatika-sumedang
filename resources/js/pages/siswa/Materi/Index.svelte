<script lang="ts">
    import { inertia, router, usePage } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import { extractId } from '@/lib/utils';
    import { formatBytes } from '@/lib/materi';
    import type { PaginationMeta } from '@/types/models';

    type MateriItem = {
        id: number;
        judul: string;
        deskripsi: string | null;
        file_name: string | null;
        file_size: number;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        dibuat_pada: string;
    };

    let {
        materis,
        kelas = null,
        matpelList = [],
        filters = { matpel: null, q: '' },
    }: {
        materis: PaginationMeta & { data: MateriItem[] };
        kelas: string | null;
        matpelList: { value: number; label: string }[];
        filters: { matpel: number | null; q: string };
    } = $props();

    let filterMatpel = $state(filters.matpel);
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    function stripHtml(html: string | null): string {
        if (!html) return '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent?.trim() ?? '';
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                matpel: filterMatpel ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['materis'],
            },
        );
    }

    function onSearchInput() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 400);
    }

    function goToPage(page: number) {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                page,
                matpel: filterMatpel ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['materis'],
            },
        );
    }

</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Lihat Materi"
        subtitle={kelas ? `Materi pembelajaran untuk kelas ${kelas} pada tahun ajaran aktif.` : 'Materi pembelajaran yang dibagikan guru kepadamu.'}
    />

    <Card class="border rounded-1 shadow-sm mb-3">
        <CardBody class="p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-matpel" class="form-label">Mata Pelajaran</label>
                    <Select
                        id="filter-matpel"
                        items={matpelList}
                        value={filterMatpel}
                        placeholder="Semua mata pelajaran"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterMatpel = extractId(v);
                            reload();
                        }}
                        onclear={() => {
                            filterMatpel = null;
                            reload();
                        }}
                    />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter-q" class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input
                            id="filter-q"
                            type="search"
                            class="form-control"
                            placeholder="Cari judul materi…"
                            value={searchInput}
                            oninput={(e) => {
                                searchInput = (e.currentTarget as HTMLInputElement).value;
                                onSearchInput();
                            }}
                        />
                    </div>
                </div>
                <div class="col-12 col-lg-4 text-lg-end text-muted small">
                    {#if materis.total > 0}
                        Menampilkan {materis.from ?? 0}–{materis.to ?? 0} dari {materis.total} materi
                    {:else}
                        Tidak ada materi
                    {/if}
                </div>
            </div>
        </CardBody>
    </Card>

    {#if materis.data.length}
        <div class="row g-3">
            {#each materis.data as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-sm h-100">
                        <CardBody class="d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <i class="bi bi-file-earmark-richtext text-primary" style="font-size: 1.6rem"></i>
                                <div class="d-flex gap-1 flex-wrap justify-content-end">
                                    <Badge color="primary">{item.matpel ?? '—'}</Badge>
                                    <Badge color="secondary">{item.kelas ?? '—'}</Badge>
                                </div>
                            </div>
                            <h2 class="h6 fw-semibold mb-1">{item.judul}</h2>
                            {#if item.deskripsi}
                                <div class="text-muted small mb-2 flex-grow-1 rich-deskripsi">
                                    {stripHtml(item.deskripsi)}
                                </div>
                            {:else}
                                <div class="flex-grow-1"></div>
                            {/if}
                            <div class="text-muted small mb-3">
                                <div>
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                                </div>
                                <div>
                                    <i class="bi bi-calendar3 me-1"></i>{item.dibuat_pada}
                                </div>
                                {#if item.file_name}
                                    <div>
                                        <i class="bi bi-paperclip me-1"></i>
                                        {item.file_name} ({formatBytes(item.file_size)})
                                    </div>
                                {/if}
                            </div>
                            <div class="d-flex gap-2">
                                <a
                                    use:inertia
                                    href={MateriController.show({ materi: item.id }).url}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                                {#if item.file_name}
                                    <a
                                        href={MateriController.unduh({ materi: item.id }).url}
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="bi bi-download me-1"></i>Unduh
                                    </a>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
        <div class="mt-3">
            <Pagination meta={materis} onPageChange={goToPage} />
        </div>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="p-0">
                <EmptyState
                    icon="bi-book-half"
                    message={
                        filterMatpel || searchInput.trim()
                            ? 'Tidak ada materi yang cocok dengan filter yang dipilih. Coba ubah atau bersihkan filter.'
                            : kelas
                              ? 'Belum ada materi yang dibagikan untuk kelasmu.'
                              : 'Kamu belum terdaftar di kelas mana pun, sehingga belum ada materi yang bisa dilihat.'
                    }
                    variant="card"
                />
            </CardBody>
        </Card>
    {/if}
</div>

<style>
.rich-deskripsi {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.rich-deskripsi p {
    margin-bottom: 0.5rem;
}

    .rich-deskripsi p:last-child {
        margin-bottom: 0;
    }

    .rich-deskripsi ul,
    .rich-deskripsi ol {
        margin-bottom: 0.5rem;
        padding-left: 1.25rem;
    }
</style>