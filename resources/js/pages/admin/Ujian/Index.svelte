<script lang="ts">
    import { inertia, router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Collapse,
        Modal,
        ModalBody,
        ModalFooter,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import { KATEGORI_INFO, DEFAULT_KATEGORI, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Admin/UjianController';
    import type { PaginationMeta, PenugasanOption } from '@/types/models';

    // ── Types ─────────────────────────────────────────────────────────────────
    type UjianItem = {
        id: number;
        judul: string;
        kategori: KategoriUjian;
        guru: string | null;
        kelas: string | null;
        matpel: string | null;
        status: 'draft' | 'terbit';
        jumlah_soal: number;
        jumlah_selesai: number;
        dibuat_oleh_admin: boolean;
        token: string | null;
        token_released: boolean;
        token_pengelola: 'guru' | 'admin';
        tanggal_mulai: string | null;
    };
    type FilterParams = { kategori: KategoriUjian | null; q: string };

    // ── Props ─────────────────────────────────────────────────────────────────
    let {
        ujians,
        penugasan = [],
        filters = { kategori: null, q: '' },
    }: {
        ujians: PaginationMeta & { data: UjianItem[] };
        penugasan: PenugasanOption[];
        filters: FilterParams;
    } = $props();

    // ── Constants ─────────────────────────────────────────────────────────────
    const kategoriOptions = (Object.keys(KATEGORI_INFO) as KategoriUjian[]).map((k) => ({
        value: k,
        label: KATEGORI_INFO[k].label,
    }));

    // ── Stats ────────────────────────────────────────────────────────────────
    const stats = $derived({
        total: ujians.total,
        terbit: ujians.data.filter((u) => u.status === 'terbit').length,
        draft: ujians.data.filter((u) => u.status === 'draft').length,
        selesai: ujians.data.reduce((s, u) => s + u.jumlah_selesai, 0),
    });

    // ── Grouped data ────────────────────────────────────────────────────────
    const terbit = $derived(ujians.data.filter((u) => u.status === 'terbit'));
    const draft = $derived(ujians.data.filter((u) => u.status === 'draft'));

    // ── Form ────────────────────────────────────────────────────────────────
    const form = useForm({
        guru_kelas_id: null as number | null,
        kategori: 'kuis' as KategoriUjian,
        judul: '',
        deskripsi: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        durasi_menit: 30,
        maks_attempt: 1,
        acak_soal: false,
        acak_opsi: false,
        tampilkan_hasil: true,
        wajib_fullscreen: false,
        maks_pelanggaran: 0,
        nilai_maks: 100,
        bobot: 1,
    });

    // ── Filter state ──────────────────────────────────────────────────────
    let filterKategori: KategoriUjian | null = $state(filters.kategori);
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    // ── Modal & collapse state ──────────────────────────────────────────────
    let modalOpen = $state(false);
    let openTokenId = $state<number | null>(null);

    // ── Helpers ────────────────────────────────────────────────────────────
    function selectValue<T>(v: unknown, fallback: T): T {
        return (v as { value: T } | null)?.value ?? fallback;
    }

    function baseUrl(): string {
        return (usePage().url as string).split('?')[0];
    }

    function buildQueryParams(page?: number): Record<string, unknown> {
        return {
            ...(page !== undefined ? { page } : {}),
            kategori: filterKategori ?? undefined,
            q: searchInput.trim() || undefined,
        };
    }

    // ── Filter actions ─────────────────────────────────────────────────────
    function reload() {
        router.get(baseUrl(), buildQueryParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['ujians', 'filters'],
        });
    }

    function goToPage(page: number) {
        router.get(baseUrl(), buildQueryParams(page), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['ujians'],
        });
    }

    function onSearchInput() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 400);
    }

    // ── Modal actions ──────────────────────────────────────────────────────
    function resetForm() {
        form.reset();
        form.clearErrors();
    }

    function openModal() {
        resetForm();
        modalOpen = true;
    }

    function applyDefaults(k: KategoriUjian) {
        const d = DEFAULT_KATEGORI[k];
        form.acak_soal = d.acak_soal;
        form.acak_opsi = d.acak_opsi;
        form.wajib_fullscreen = d.wajib_fullscreen;
        form.maks_pelanggaran = d.maks_pelanggaran;
        form.bobot = d.bobot;
    }

    function submit() {
        form.post(UjianController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen = false;
                resetForm();
            },
        });
    }

    // ── Row actions ─────────────────────────────────────────────────────────
    function togglePublish(item: UjianItem) {
        router.post(UjianController.terbit({ ujian: item.id }).url, {}, { preserveScroll: true });
    }

    function setPengelola(item: UjianItem, val: 'guru' | 'admin') {
        router.post(
            UjianController.setPengelolaToken({ ujian: item.id }).url,
            { token_pengelola: val },
            { preserveScroll: true },
        );
    }

    function generateToken(item: UjianItem) {
        router.post(UjianController.generateToken({ ujian: item.id }).url, {}, { preserveScroll: true });
    }

    function toggleTokenRelease(item: UjianItem) {
        router.post(UjianController.toggleToken({ ujian: item.id }).url, {}, { preserveScroll: true });
    }

    async function hapus(item: UjianItem) {
        const ok = await confirm.show({
            title: 'Hapus Ujian',
            message: `Ujian "${item.judul}" milik ${item.guru ?? 'guru'} akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(UjianController.destroy({ ujian: item.id }).url, { preserveScroll: true });
    }

    function toggleTokenPanel(id: number) {
        openTokenId = openTokenId === id ? null : id;
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Ujian (CBT)"
        subtitle="Pantau dan kelola seluruh ujian lintas guru."
    >
        {#snippet actions()}
            <Button color="primary" onclick={openModal}>
                <i class="bi bi-plus-lg me-1"></i>Buat Ujian
            </Button>
        {/snippet}
    </PageHeader>

    <!-- ── Stats Row ─────────────────────────────────────────────────────── -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-3">
            <div class="card border">
                <div class="card-body p-2 text-center">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Total</div>
                    <div class="fw-bold" style="font-size:1.5rem;line-height:1">{stats.total}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-success">
                <div class="card-body p-2 text-center">
                    <div class="text-success text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Terbit</div>
                    <div class="fw-bold text-success" style="font-size:1.5rem;line-height:1">{stats.terbit}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <div class="text-warning text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Draft</div>
                    <div class="fw-bold text-warning" style="font-size:1.5rem;line-height:1">{stats.draft}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border">
                <div class="card-body p-2 text-center">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Pengerjaan</div>
                    <div class="fw-bold" style="font-size:1.5rem;line-height:1">{stats.selesai}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Main Card ─────────────────────────────────────────────────── -->
    <Card>
        <CardBody class="p-0">

            <!-- ── Filter Bar ─────────────────────────────────────────── -->
            <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom">
                <div style="min-width:160px">
                    <Select
                        items={kategoriOptions}
                        value={filterKategori}
                        placeholder="Semua kategori"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => { filterKategori = selectValue(v, null); reload(); }}
                    />
                </div>
                <div class="input-group input-group-sm ms-auto" style="max-width:220px">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control" placeholder="Cari…" bind:value={searchInput} onkeyup={onSearchInput} />
                </div>
            </div>

            <!-- ── Empty State ─────────────────────────────────────────── -->
            {#if ujians.data.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-journal-x display-5 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold mb-1">Belum ada ujian.</div>
                    <div class="small mb-3">Buat ujian baru untuk mulai.</div>
                    <Button color="primary" onclick={openModal}>
                        <i class="bi bi-plus-lg me-1"></i>Buat Ujian
                    </Button>
                </div>

            <!-- ── Grouped List ───────────────────────────────────────── -->
            {:else}

                <!-- Terbit -->
                {#if terbit.length > 0}
                    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                        <i class="bi bi-broadcast text-success"></i>
                        <span class="fw-semibold small">Terbit</span>
                        <span class="badge bg-light text-dark border ms-auto" style="font-size:0.65rem">{terbit.length} ujian</span>
                    </div>
                    {#each terbit as item (item.id)}
                        <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom">
                            <div class={`rounded flex-shrink-0 accent-bar accent-${item.kategori === 'uts' ? 'primary' : item.kategori === 'uas' ? 'warning' : item.kategori === 'usbk' ? 'danger' : 'info'}`}></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <Badge color={KATEGORI_INFO[item.kategori].color}>{KATEGORI_INFO[item.kategori].label}</Badge>
                                    <span class="fw-semibold" style="font-size:0.9rem">{item.judul}</span>
                                    {#if item.dibuat_oleh_admin}
                                        <Badge color="dark" style="font-size:0.63rem">admin</Badge>
                                    {/if}
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i>{item.guru ?? '—'}
                                    <span class="mx-1">·</span>
                                    <i class="bi bi-collection me-1"></i>{item.kelas ?? '—'} · {item.matpel ?? '—'}
                                    {#if item.tanggal_mulai}
                                        <span class="mx-1">·</span>
                                        <i class="bi bi-calendar-event me-1"></i>{item.tanggal_mulai}
                                    {/if}
                                </div>
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <span class="small"><i class="bi bi-list-check me-1"></i><strong>{item.jumlah_soal}</strong> soal</span>
                                    <span class="small"><i class="bi bi-check-circle me-1"></i><strong>{item.jumlah_selesai}</strong> selesai</span>
                                </div>

                                <!-- Token Panel -->
                                {#if item.token_pengelola === 'admin'}
                                    <div class="mt-2">
                                        <button
                                            class="btn btn-sm btn-link p-0 text-decoration-none small"
                                            onclick={() => toggleTokenPanel(item.id)}
                                        >
                                            <i class={`bi ${openTokenId === item.id ? 'bi-chevron-up' : 'bi-chevron-down'} me-1`}></i>
                                            Token
                                            {#if item.token}
                                                <code class="ms-1">{item.token}</code>
                                                <Badge color={item.token_released ? 'success' : 'secondary'} style="font-size:0.63rem">
                                                    {item.token_released ? 'Rilis' : 'Ditahan'}
                                                </Badge>
                                            {:else}
                                                <span class="text-muted ms-1">belum dibuat</span>
                                            {/if}
                                        </button>
                                        <Collapse isOpen={openTokenId === item.id}>
                                            <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                                                <select
                                                    class="form-select form-select-sm"
                                                    style="width:auto"
                                                    value={item.token_pengelola}
                                                    onchange={(e) => setPengelola(item, (e.currentTarget as HTMLSelectElement).value as 'guru' | 'admin')}
                                                >
                                                    <option value="guru">Guru</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                                <Button size="sm" color="outline-secondary" onclick={() => generateToken(item)} title="Buat/regenerasi token">
                                                    <i class="bi bi-key me-1"></i>Buat Token
                                                </Button>
                                                {#if item.token}
                                                    <Button
                                                        size="sm"
                                                        color={item.token_released ? 'outline-warning' : 'outline-success'}
                                                        onclick={() => toggleTokenRelease(item)}
                                                        title={item.token_released ? 'Tahan token' : 'Rilis token'}
                                                    >
                                                        <i class={`bi ${item.token_released ? 'bi-lock' : 'bi-unlock'} me-1`}></i>
                                                        {item.token_released ? 'Tahan' : 'Rilis'}
                                                    </Button>
                                                {/if}
                                            </div>
                                        </Collapse>
                                    </div>
                                {:else}
                                    <div class="mt-2">
                                        <span class="small text-muted">
                                            <i class="bi bi-shield-lock me-1"></i>Token dikelola oleh guru pengampu
                                        </span>
                                    </div>
                                {/if}
                            </div>
                            <div class="d-inline-flex gap-1 flex-shrink-0">
                                <a use:inertia href={UjianController.soal({ ujian: item.id }).url} class="btn btn-sm btn-outline-primary" title="Lihat soal">
                                    <i class="bi bi-list-check"></i>
                                </a>
                                <a use:inertia href={UjianController.hasil({ ujian: item.id }).url} class="btn btn-sm btn-outline-info" title="Lihat hasil">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <Button size="sm" color="outline-warning" onclick={() => togglePublish(item)} title="Tarik ke draft">
                                    <i class="bi bi-eye-slash"></i>
                                </Button>
                                <Button size="sm" color="outline-danger" onclick={() => hapus(item)} title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </Button>
                            </div>
                        </div>
                    {/each}
                {/if}

                <!-- Draft -->
                {#if draft.length > 0}
                    <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom mt-4">
                        <i class="bi bi-pencil-square text-warning"></i>
                        <span class="fw-semibold small">Draft</span>
                        <span class="badge bg-light text-dark border ms-auto" style="font-size:0.65rem">{draft.length} ujian</span>
                    </div>
                    {#each draft as item (item.id)}
                        <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom">
                            <div class={`rounded flex-shrink-0 accent-bar accent-${item.kategori === 'uts' ? 'primary' : item.kategori === 'uas' ? 'warning' : item.kategori === 'usbk' ? 'danger' : 'info'}`}></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <Badge color={KATEGORI_INFO[item.kategori].color}>{KATEGORI_INFO[item.kategori].label}</Badge>
                                    <span class="fw-semibold" style="font-size:0.9rem">{item.judul}</span>
                                    {#if item.dibuat_oleh_admin}
                                        <Badge color="dark" style="font-size:0.63rem">admin</Badge>
                                    {/if}
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-person me-1"></i>{item.guru ?? '—'}
                                    <span class="mx-1">·</span>
                                    <i class="bi bi-collection me-1"></i>{item.kelas ?? '—'} · {item.matpel ?? '—'}
                                    {#if item.tanggal_mulai}
                                        <span class="mx-1">·</span>
                                        <i class="bi bi-calendar-event me-1"></i>{item.tanggal_mulai}
                                    {/if}
                                </div>
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <span class="small"><i class="bi bi-list-check me-1"></i><strong>{item.jumlah_soal}</strong> soal</span>
                                    <span class="small"><i class="bi bi-check-circle me-1"></i><strong>{item.jumlah_selesai}</strong> selesai</span>
                                </div>
                            </div>
                            <div class="d-inline-flex gap-1 flex-shrink-0">
                                <a use:inertia href={UjianController.soal({ ujian: item.id }).url} class="btn btn-sm btn-outline-primary" title="Lihat soal">
                                    <i class="bi bi-list-check"></i>
                                </a>
                                <a use:inertia href={UjianController.hasil({ ujian: item.id }).url} class="btn btn-sm btn-outline-info" title="Lihat hasil">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <Button size="sm" color="outline-success" onclick={() => togglePublish(item)} title="Terbitkan">
                                    <i class="bi bi-send"></i>
                                </Button>
                                <Button size="sm" color="outline-danger" onclick={() => hapus(item)} title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </Button>
                            </div>
                        </div>
                    {/each}
                {/if}

            {/if}

            <Pagination meta={ujians} onPageChange={goToPage} />
        </CardBody>
    </Card>
    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>
            <i class="bi bi-journal-plus me-2"></i>Buat Ujian
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">

                <!-- Baris 1: Penugasan + Kategori -->
                <div class="col-md-8">
                    <label class="form-label" for="a-penugasan">Guru · Kelas · Matpel</label>
                    <Select
                        id="a-penugasan"
                        items={penugasan}
                        value={form.guru_kelas_id}
                        placeholder="Pilih penugasan"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => { form.guru_kelas_id = selectValue(v, null); }}
                    />
                    {#if form.errors.guru_kelas_id}<div class="text-danger small">{form.errors.guru_kelas_id}</div>{/if}
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="a-kategori">Kategori</label>
                    <select
                        id="a-kategori"
                        class="form-select"
                        value={form.kategori}
                        onchange={(e) => {
                            form.kategori = (e.currentTarget as HTMLSelectElement).value as KategoriUjian;
                            applyDefaults(form.kategori);
                        }}
                    >
                        {#each kategoriOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>

                <!-- Baris 2: Judul -->
                <div class="col-12">
                    <label class="form-label" for="a-judul">Judul Ujian</label>
                    <input id="a-judul" class="form-control" placeholder="cth: UH 1 Matematika Kelas X" bind:value={form.judul} />
                    {#if form.errors.judul}<div class="text-danger small">{form.errors.judul}</div>{/if}
                </div>

                <!-- Baris 3: Jadwal (kondisi UT S/ UAS / USBK) -->
                {#if (form.kategori === 'uts' || form.kategori === 'uas' || form.kategori === 'usbk')}
                    <div class="col-12">
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="bi bi-calendar-week me-1"></i>
                            <strong>{KATEGORI_INFO[form.kategori]?.label}:</strong> wajib jadwal. Jadwal harus berada dalam periode ujian yang berlaku.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="a-mulai">Jadwal Mulai</label>
                        <input id="a-mulai" type="datetime-local" class="form-control" bind:value={form.tanggal_mulai} />
                        {#if form.errors.tanggal_mulai}<div class="text-danger small">{form.errors.tanggal_mulai}</div>{/if}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="a-selesai">Jadwal Selesai</label>
                        <input id="a-selesai" type="datetime-local" class="form-control" bind:value={form.tanggal_selesai} />
                    </div>
                {/if}

                <!-- Baris 4: Durasi, Nilai Maks, Bobot -->
                <div class="col-md-4">
                    <label class="form-label" for="a-durasi">Durasi (menit)</label>
                    <input id="a-durasi" type="number" min="1" class="form-control" bind:value={form.durasi_menit} />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="a-nilaimaks">Nilai Maks</label>
                    <input id="a-nilaimaks" type="number" min="1" class="form-control" bind:value={form.nilai_maks} />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="a-bobot">Bobot</label>
                    <input id="a-bobot" type="number" min="1" class="form-control" bind:value={form.bobot} />
                </div>

                <!-- Baris 5: Pengaturan kategori (acak, fullscreen, pelanggaran) -->
                <div class="col-12">
                    <label class="form-label small text-muted">Pengaturan</label>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="a-acaksoal" bind:checked={form.acak_soal} />
                                <label class="form-check-label small" for="a-acaksoal">Acak soal</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="a-acakopsi" bind:checked={form.acak_opsi} />
                                <label class="form-check-label small" for="a-acakopsi">Acak opsi</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="a-tampilk_hasil" bind:checked={form.tampilkan_hasil} />
                                <label class="form-check-label small" for="a-tampilk_hasil">Tampilkan hasil</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="a-fullscreen" bind:checked={form.wajib_fullscreen} />
                                <label class="form-check-label small" for="a-fullscreen">Wajib fullscreen</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label small" for="a-maksattempt">Maks attempt</label>
                            <input id="a-maksattempt" type="number" min="1" max="10" class="form-control" bind:value={form.maks_attempt} />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small" for="a-makspelanggaran">Maks pelanggaran</label>
                            <input id="a-makspelanggaran" type="number" min="0" max="50" class="form-control" bind:value={form.maks_pelanggaran} />
                        </div>
                    </div>
                </div>

            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={submit} disabled={form.processing}>
                <i class="bi bi-check-lg me-1"></i>Simpan
            </Button>
        </ModalFooter>
    </Modal>
</div>

<style>
    .accent-bar { width: 4px; min-height: 36px; }
    .accent-primary   { background: var(--bs-primary); }
    .accent-warning   { background: var(--bs-warning); }
    .accent-danger    { background: var(--bs-danger); }
    .accent-info      { background: var(--bs-info); }
</style>
