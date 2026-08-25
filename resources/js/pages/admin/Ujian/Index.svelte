<script lang="ts">
    import { inertia, router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Modal,
        ModalBody,
        ModalFooter,
        ModalHeader,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Pagination from '@/components/Pagination.svelte';
    import Select from '@/components/Select.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import { extractId } from '@/lib/utils';
    import { KATEGORI_INFO, DEFAULT_KATEGORI, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Admin/UjianController';
    import type { PaginationMeta, PenugasanOption } from '@/types/models';

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

    let {
        ujians,
        penugasan = [],
        filters = { kategori: null, q: '' },
    }: {
        ujians: PaginationMeta & { data: UjianItem[] };
        penugasan: PenugasanOption[];
        filters: { kategori: KategoriUjian | null; q: string };
    } = $props();

    const kategoriOptions = (Object.keys(KATEGORI_INFO) as KategoriUjian[]).map((k) => ({
        value: k,
        label: KATEGORI_INFO[k].label,
    }));

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

    // svelte-ignore state_referenced_locally
    let filterKategori: KategoriUjian | null = $state(filters.kategori);
    // svelte-ignore state_referenced_locally
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let modalOpen = $state(false);

    function terapkanDefault(k: KategoriUjian) {
        const d = DEFAULT_KATEGORI[k];
        form.acak_soal = d.acak_soal;
        form.acak_opsi = d.acak_opsi;
        form.wajib_fullscreen = d.wajib_fullscreen;
        form.maks_pelanggaran = d.maks_pelanggaran;
        form.bobot = d.bobot;
    }

    function buat() {
        form.post(UjianController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                modalOpen = false;
            },
        });
    }

    function terbitkan(item: UjianItem) {
        router.post(UjianController.terbit({ ujian: item.id }).url, {}, { preserveScroll: true });
    }

    function setPengelola(item: UjianItem, pengelola: 'guru' | 'admin') {
        router.post(
            UjianController.setPengelolaToken({ ujian: item.id }).url,
            { token_pengelola: pengelola },
            { preserveScroll: true },
        );
    }

    function buatToken(item: UjianItem) {
        router.post(UjianController.generateToken({ ujian: item.id }).url, {}, { preserveScroll: true });
    }

    function toggleToken(item: UjianItem) {
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

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            { kategori: filterKategori ?? undefined, q: searchInput.trim() || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['ujians', 'filters'] },
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
            { page, kategori: filterKategori ?? undefined, q: searchInput.trim() || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['ujians'] },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Ujian (CBT)"
        subtitle="Pantau dan kelola seluruh ujian lintas guru."
    >
        {#snippet actions()}
            <Button color="primary" onclick={() => (modalOpen = true)}>
                <i class="bi bi-plus-lg me-1"></i>Buat Ujian
            </Button>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <div style="min-width: 160px">
                    <Select
                        id="filter-kategori"
                        items={kategoriOptions}
                        value={filterKategori}
                        placeholder="Semua kategori"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterKategori = (v as KategoriUjian) ?? null;
                            reload();
                        }}
                    />
                </div>
                <div class="input-group input-group-sm" style="max-width: 260px">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Cari judul…"
                        bind:value={searchInput}
                        onkeyup={onSearchInput}
                    />
                </div>
            </div>

            {#if ujians.data.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-journal-x display-5 d-block mb-2"></i>
                    <div>Belum ada ujian.</div>
                </div>
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Guru</th>
                                <th>Kelas & Matpel</th>
                                <th>Soal</th>
                                <th>Status</th>
                                <th>Token</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each ujians.data as item (item.id)}
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{item.judul}</div>
                                        {#if item.dibuat_oleh_admin}
                                            <Badge color="dark" pill>oleh admin</Badge>
                                        {/if}
                                    </td>
                                    <td>
                                        <Badge color={KATEGORI_INFO[item.kategori].color} pill>
                                            {KATEGORI_INFO[item.kategori].label}
                                        </Badge>
                                    </td>
                                    <td class="small">{item.guru ?? '—'}</td>
                                    <td class="small text-nowrap">
                                        {item.kelas ?? '—'} · {item.matpel ?? '—'}
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{item.jumlah_soal}</span>
                                        <div class="text-muted small">{item.jumlah_selesai} selesai</div>
                                    </td>
                                    <td>
                                        {#if item.status === 'terbit'}
                                            <Badge color="success" pill>Terbit</Badge>
                                        {:else}
                                            <Badge color="warning" pill>Draft</Badge>
                                        {/if}
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <select
                                                class="form-select form-select-sm"
                                                style="width: auto"
                                                value={item.token_pengelola}
                                                onchange={(e) =>
                                                    setPengelola(item, (e.currentTarget as HTMLSelectElement).value as 'guru' | 'admin')}
                                                title="Pengelola token"
                                            >
                                                <option value="guru">Guru</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                        {#if item.token_pengelola === 'admin'}
                                            <div class="d-flex align-items-center gap-1">
                                                {#if item.token}
                                                    <code class="fs-6">{item.token}</code>
                                                    {#if item.token_released}
                                                        <Badge color="success" pill>Rilis</Badge>
                                                    {:else}
                                                        <Badge color="secondary" pill>Tahan</Badge>
                                                    {/if}
                                                {/if}
                                                <Button size="sm" color="outline-secondary" onclick={() => buatToken(item)} title="Buat/regenerasi token">
                                                    <i class="bi bi-key"></i>
                                                </Button>
                                                {#if item.token}
                                                    <Button
                                                        size="sm"
                                                        color={item.token_released ? 'outline-warning' : 'outline-success'}
                                                        onclick={() => toggleToken(item)}
                                                        title={item.token_released ? 'Tahan token' : 'Rilis token'}
                                                    >
                                                        <i class={`bi ${item.token_released ? 'bi-lock' : 'bi-unlock'}`}></i>
                                                    </Button>
                                                {/if}
                                            </div>
                                        {:else}
                                            <span class="text-muted small">Dikelola guru</span>
                                        {/if}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a
                                                use:inertia
                                                href={UjianController.soal({ ujian: item.id }).url}
                                                class="btn btn-sm btn-outline-primary"
                                                title="Lihat soal"
                                            >
                                                <i class="bi bi-list-check"></i>
                                            </a>
                                            <a
                                                use:inertia
                                                href={UjianController.hasil({ ujian: item.id }).url}
                                                class="btn btn-sm btn-outline-info"
                                                title="Lihat hasil"
                                            >
                                                <i class="bi bi-bar-chart"></i>
                                            </a>
                                            <Button
                                                size="sm"
                                                color={item.status === 'terbit' ? 'outline-warning' : 'outline-success'}
                                                onclick={() => terbitkan(item)}
                                                title={item.status === 'terbit' ? 'Tarik' : 'Terbitkan'}
                                            >
                                                <i class={`bi ${item.status === 'terbit' ? 'bi-eye-slash' : 'bi-send'}`}></i>
                                            </Button>
                                            <Button size="sm" color="outline-danger" onclick={() => hapus(item)}>
                                                <i class="bi bi-trash"></i>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
                <Pagination meta={ujians} onPageChange={goToPage} />
            {/if}
        </CardBody>
    </Card>

    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>Buat Ujian</ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="a-penugasan">Guru · Kelas · Matpel</label>
                    <Select
                        id="a-penugasan"
                        items={penugasan}
                        value={form.guru_kelas_id}
                        placeholder="Pilih penugasan"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => (form.guru_kelas_id = extractId(v))}
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
                            terapkanDefault(form.kategori);
                        }}
                    >
                        {#each kategoriOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="a-judul">Judul</label>
                    <input id="a-judul" class="form-control" bind:value={form.judul} />
                    {#if form.errors.judul}<div class="text-danger small">{form.errors.judul}</div>{/if}
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
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={buat} disabled={form.processing}>Simpan</Button>
        </ModalFooter>
    </Modal>
</div>
