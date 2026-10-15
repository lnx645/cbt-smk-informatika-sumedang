<script lang="ts">
    import { inertia, router, useForm, usePage } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Dropdown,
        DropdownItem,
        DropdownMenu,
        DropdownToggle,
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
    import {
        KATEGORI_INFO,
        DEFAULT_KATEGORI,
        type KategoriUjian,
    } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import SoalController from '@/actions/App/Http/Controllers/Guru/SoalController';
    import HasilUjianController from '@/actions/App/Http/Controllers/Guru/HasilUjianController';
    import AkademikController from '@/actions/App/Http/Controllers/Guru/UjianAkademikController';
    import type { PaginationMeta, PenugasanOption } from '@/types/models';

    type UjianItem = {
        id: number;
        judul: string;
        kategori: KategoriUjian;
        kelas: string | null;
        matpel: string | null;
        status: 'draft' | 'terbit';
        durasi_menit: number;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        jumlah_soal: number;
        jumlah_sesi: number;
        jumlah_selesai: number;
        token: string | null;
        token_released: boolean;
        token_pengelola: 'guru' | 'admin';
        nilai_maks: number;
        bobot: number;
    };
    type EditUjian = {
        id: number;
        guru_kelas_id: number | null;
        kategori: KategoriUjian;
        judul: string;
        deskripsi: string | null;
        tata_tertib: string | null;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        durasi_menit: number;
        maks_attempt: number;
        acak_soal: boolean;
        acak_opsi: boolean;
        tampilkan_hasil: boolean;
        wajib_fullscreen: boolean;
        maks_pelanggaran: number;
        nilai_maks: number;
        bobot: number;
        token_pengelola: 'guru' | 'admin';
    };

    let {
        ujians,
        penugasan = [],
        filters = { guru_kelas_id: null, kategori: null, q: '' },
        editUjian = null,
    }: {        ujians: PaginationMeta & { data: UjianItem[] };
        penugasan: PenugasanOption[];
        filters: {
            guru_kelas_id: number | null;
            kategori: KategoriUjian | null;
            q: string;
        };
        editUjian: EditUjian | null;
    } = $props();

    const kategoriOptions = (
        Object.keys(KATEGORI_INFO) as KategoriUjian[]
    ).map((k) => ({ value: k, label: KATEGORI_INFO[k].label }));

    function baseForm() {
        return {
            guru_kelas_id: null as number | null,
            kategori: 'kuis' as KategoriUjian,
            judul: '',
            deskripsi: '',
            tata_tertib: '',
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
            token_pengelola: 'guru' as 'guru' | 'admin',
        };
    }

    const form = useForm(baseForm());
    const editForm = useForm(baseForm());

    // svelte-ignore state_referenced_locally
    let filterGuruKelasId = $state(filters.guru_kelas_id);
    // svelte-ignore state_referenced_locally
    let filterKategori: KategoriUjian | null = $state(filters.kategori);
    // svelte-ignore state_referenced_locally
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    let modalOpen = $state(false);
    let editOpen = $state(false);

    function terapkanDefault(f: ReturnType<typeof useForm>, k: KategoriUjian) {
        const d = DEFAULT_KATEGORI[k];
        f.acak_soal = d.acak_soal;
        f.acak_opsi = d.acak_opsi;
        f.wajib_fullscreen = d.wajib_fullscreen;
        f.maks_pelanggaran = d.maks_pelanggaran;
        f.bobot = d.bobot;
    }

    $effect(() => {
        if (!editUjian) return;
        editForm.guru_kelas_id = editUjian.guru_kelas_id;
        editForm.kategori = editUjian.kategori;
        editForm.judul = editUjian.judul;
        editForm.deskripsi = editUjian.deskripsi ?? '';
        editForm.tata_tertib = editUjian.tata_tertib ?? '';
        editForm.tanggal_mulai = editUjian.tanggal_mulai ?? '';
        editForm.tanggal_selesai = editUjian.tanggal_selesai ?? '';
        editForm.durasi_menit = editUjian.durasi_menit;
        editForm.maks_attempt = editUjian.maks_attempt;
        editForm.acak_soal = editUjian.acak_soal;
        editForm.acak_opsi = editUjian.acak_opsi;
        editForm.tampilkan_hasil = editUjian.tampilkan_hasil;
        editForm.wajib_fullscreen = editUjian.wajib_fullscreen;
        editForm.maks_pelanggaran = editUjian.maks_pelanggaran;
        editForm.nilai_maks = editUjian.nilai_maks;
        editForm.bobot = editUjian.bobot;
        editForm.token_pengelola = editUjian.token_pengelola ?? 'guru';
    });

    function buat() {
        if (!form.guru_kelas_id) {
            form.setError('guru_kelas_id', 'Pilih kelas & mata pelajaran.');
            return;
        }
        form.post(UjianController.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                modalOpen = false;
            },
        });
    }

    function bukaEdit(item: UjianItem) {
        router.get(
            UjianController.edit({ ujian: item.id }).url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['editUjian'],
                onSuccess: () => (editOpen = true),
            },
        );
    }

    function simpanEdit() {
        if (!editUjian) return;
        editForm.put(UjianController.update({ ujian: editUjian.id }).url, {
            preserveScroll: true,
            onSuccess: () => (editOpen = false),
        });
    }

    function terbitkan(item: UjianItem) {
        router.post(
            UjianController.terbit({ ujian: item.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    function buatToken(item: UjianItem) {
        router.post(
            UjianController.generateToken({ ujian: item.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    function toggleToken(item: UjianItem) {
        router.post(
            UjianController.toggleToken({ ujian: item.id }).url,
            {},
            { preserveScroll: true },
        );
    }

    async function hapus(item: UjianItem) {
        const ok = await confirm.show({
            title: 'Hapus Ujian',
            message: `Ujian "${item.judul}" beserta soal & hasil pengerjaan akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(UjianController.destroy({ ujian: item.id }).url, {
            preserveScroll: true,
        });
    }

    async function duplikat(item: UjianItem) {
        const ok = await confirm.show({
            title: 'Duplikat Ujian',
            message: `Buat salinan "${item.judul}" sebagai draft beserta soal dan sesinya?`,
            confirmText: 'Ya, Duplikat',
            color: 'primary',
        });
        if (!ok) return;
        router.post(AkademikController.duplikat({ ujian: item.id }).url, {}, {
            preserveScroll: true,
        });
    }

    function reload() {
        const url = (usePage().url as string).split('?')[0];
        router.get(
            url,
            {
                guru_kelas_id: filterGuruKelasId ?? undefined,
                kategori: filterKategori ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['ujians', 'filters'],
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
                guru_kelas_id: filterGuruKelasId ?? undefined,
                kategori: filterKategori ?? undefined,
                q: searchInput.trim() || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['ujians'],
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Ujian (CBT)"
        subtitle="Buat kuis dan ujian besar, kelola soal, token, dan pantau hasil."
    >
        {#snippet actions()}
            <a href="/app/guru/ujian/wizard?mode=santai" class="btn btn-outline-primary">
                <i class="bi bi-lightning-charge me-1"></i>Buat Kuis
            </a>
            <a href="/app/guru/ujian/wizard?mode=resmi" class="btn btn-primary">
                <i class="bi bi-shield-check me-1"></i>Buat Ujian Resmi
            </a>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div style="min-width: 240px">
                    <Select
                        id="filter-penugasan"
                        items={penugasan}
                        value={filterGuruKelasId}
                        placeholder="Semua kelas"
                        clearable={true}
                        getOptionValue={(item) => item.value}
                        onchange={(v) => {
                            filterGuruKelasId = extractId(v);
                            reload();
                        }}
                    />
                </div>
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
                    <span class="input-group-text bg-body"
                        ><i class="bi bi-search"></i></span
                    >
                    <input
                        type="search"
                        class="form-control"
                        placeholder="Cari judul ujian…"
                        bind:value={searchInput}
                        onkeyup={onSearchInput}
                    />
                </div>
            </div>

            {#if ujians.data.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-journal-x display-5 d-block mb-2"></i>
                    <div>Belum ada ujian. Klik "Buat Ujian" untuk mulai.</div>
                </div>
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Kelas & Matpel</th>
                                <th>Soal</th>
                                <th>Token</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each ujians.data as item (item.id)}
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{item.judul}</div>
                                        <div class="text-muted small">
                                            {item.durasi_menit} menit · bobot {item.bobot}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge color={KATEGORI_INFO[item.kategori]?.color ?? 'secondary'} pill>
                                            {KATEGORI_INFO[item.kategori]?.label ?? item.kategori}
                                        </Badge>
                                    </td>
                                    <td class="text-nowrap small">
                                        {item.kelas ?? 'Kelas'}
                                        <span class="text-muted">·</span>
                                        {item.matpel ?? 'Matpel'}
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="fw-semibold">{item.jumlah_soal}</span>
                                        soal
                                        <div class="text-muted small">
                                            {item.jumlah_selesai} selesai
                                        </div>
                                        {#if item.status !== 'terbit'}
                                            <div class="mt-1 d-flex gap-1 small">
                                                <span class="badge {item.jumlah_soal > 0 ? 'text-bg-success' : 'text-bg-secondary'}" title="Langkah 2: Isi soal">
                                                    <i class="bi bi-check-circle me-1"></i>Soal
                                                </span>
                                                <span class="badge {item.jumlah_sesi > 0 ? 'text-bg-success' : 'text-bg-secondary'}" title="Langkah 3: Atur sesi">
                                                    <i class="bi bi-check-circle me-1"></i>Sesi
                                                </span>
                                            </div>
                                        {/if}
                                    </td>
                                    <td class="text-nowrap">
                                        {#if item.token_pengelola === 'admin'}
                                            <span class="badge bg-light text-dark border" title="Token dikelola admin">
                                                <i class="bi bi-shield-lock me-1"></i>oleh admin
                                            </span>
                                        {:else if item.token}
                                            <code class="fs-6">{item.token}</code>
                                            {#if item.token_released}
                                                <Badge color="success" pill class="ms-1">Rilis</Badge>
                                            {:else}
                                                <Badge color="secondary" pill class="ms-1">Tahan</Badge>
                                            {/if}
                                        {:else}
                                            <span class="text-muted">—</span>
                                        {/if}
                                    </td>
                                    <td>
                                        {#if item.status === 'terbit'}
                                            <Badge color="success" pill>Terbit</Badge>
                                        {:else}
                                            <Badge color="warning" pill>Draft</Badge>
                                        {/if}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            {#if item.status === 'terbit'}
                                                <Button size="sm" color="outline-secondary" onclick={() => terbitkan(item)} title="Tarik kembali ke draft">
                                                    <i class="bi bi-eye-slash me-1"></i>Tarik
                                                </Button>
                                            {:else}
                                                <Button size="sm" color="success" onclick={() => terbitkan(item)} title="Terbitkan agar bisa dikerjakan siswa">
                                                    <i class="bi bi-send me-1"></i>Terbitkan
                                                </Button>
                                            {/if}
                                            <a
                                                use:inertia
                                                href={SoalController.index({ ujian: item.id }).url}
                                                class="btn btn-sm btn-outline-primary"
                                                title="Kelola soal"
                                            >
                                                <i class="bi bi-list-check me-1"></i>Soal
                                                {#if item.jumlah_soal > 0}
                                                    <span class="badge text-bg-primary ms-1">{item.jumlah_soal}</span>
                                                {/if}
                                            </a>
                                            <a
                                                use:inertia
                                                href={UjianController.sesiIndex({ ujian: item.id }).url}
                                                class="btn btn-sm btn-outline-warning"
                                                title="Kelola sesi"
                                            >
                                                <i class="bi bi-clock-history me-1"></i>Sesi
                                                {#if item.jumlah_sesi > 0}
                                                    <span class="badge text-bg-warning ms-1">{item.jumlah_sesi}</span>
                                                {/if}
                                            </a>
                                            <a
                                                use:inertia
                                                href={HasilUjianController.index({ ujian: item.id }).url}
                                                class="btn btn-sm btn-outline-info"
                                                title="Lihat hasil"
                                            >
                                                <i class="bi bi-bar-chart me-1"></i>Hasil
                                            </a>
                                            <Dropdown size="sm">
                                                <DropdownToggle color="outline-secondary" caret>
                                                    <i class="bi bi-three-dots"></i>
                                                </DropdownToggle>
                                                <DropdownMenu end>
                                                    <DropdownItem>
                                                        <a use:inertia href={UjianController.sesiIndex({ ujian: item.id }).url} class="text-decoration-none text-dark d-block">
                                                            <i class="bi bi-clock-history me-2"></i>Kelola Sesi
                                                        </a>
                                                    </DropdownItem>
                                                    <DropdownItem>
                                                        <a use:inertia href={AkademikController.analisisIndex({ ujian: item.id }).url} class="text-decoration-none text-dark d-block">
                                                            <i class="bi bi-graph-up me-2"></i>Analisis Soal
                                                        </a>
                                                    </DropdownItem>
                                                    <DropdownItem>
                                                        <a use:inertia href={AkademikController.beritaAcaraIndex({ ujian: item.id }).url} class="text-decoration-none text-dark d-block">
                                                            <i class="bi bi-file-earmark-text me-2"></i>Berita Acara
                                                        </a>
                                                    </DropdownItem>
                                                    <DropdownItem>
                                                        <a use:inertia href={AkademikController.remedialIndex({ ujian: item.id }).url} class="text-decoration-none text-dark d-block">
                                                            <i class="bi bi-arrow-repeat me-2"></i>Remedial
                                                        </a>
                                                    </DropdownItem>
                                                    <DropdownItem divider />
                                                    <DropdownItem onclick={() => bukaEdit(item)}>
                                                        <i class="bi bi-pencil me-2"></i>Edit Ujian
                                                    </DropdownItem>
                                                    <DropdownItem onclick={() => duplikat(item)}>
                                                        <i class="bi bi-copy me-2"></i>Duplikat
                                                    </DropdownItem>
                                                    <DropdownItem divider />
                                                    <DropdownItem onclick={() => buatToken(item)} disabled={item.token_pengelola === 'admin'}>
                                                        <i class="bi bi-key me-2"></i>Buat Token
                                                    </DropdownItem>
                                                    {#if item.token && item.token_pengelola === 'guru'}
                                                        <DropdownItem onclick={() => toggleToken(item)}>
                                                            <i class={`bi ${item.token_released ? 'bi-lock' : 'bi-unlock'} me-2`}></i>{item.token_released ? 'Tahan Token' : 'Rilis Token'}
                                                        </DropdownItem>
                                                    {/if}
                                                    <DropdownItem divider />
                                                    <DropdownItem onclick={() => hapus(item)} class="text-danger">
                                                        <i class="bi bi-trash me-2"></i>Hapus
                                                    </DropdownItem>
                                                </DropdownMenu>
                                            </Dropdown>
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
            {@render formFields(form)}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={buat} disabled={form.processing}>
                Simpan
            </Button>
        </ModalFooter>
    </Modal>

    <Modal isOpen={editOpen} toggle={() => (editOpen = !editOpen)} size="lg">
        <ModalHeader toggle={() => (editOpen = false)}>Edit Ujian</ModalHeader>
        <ModalBody>
            {@render formFields(editForm)}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (editOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpanEdit} disabled={editForm.processing}>
                Perbarui
            </Button>
        </ModalFooter>
    </Modal>
</div>

{#snippet formFields(f: ReturnType<typeof useForm>)}
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="f-penugasan">Kelas & Mata Pelajaran</label>
            <Select
                id="f-penugasan"
                items={penugasan}
                value={f.guru_kelas_id}
                placeholder="Pilih penugasan"
                getOptionValue={(item) => item.value}
                onchange={(v) => (f.guru_kelas_id = extractId(v))}
            />
            {#if f.errors.guru_kelas_id}
                <div class="text-danger small">{f.errors.guru_kelas_id}</div>
            {/if}
        </div>
        <div class="col-md-6">
            <label class="form-label" for="f-kategori">Kategori</label>
            <select
                id="f-kategori"
                class="form-select"
                value={f.kategori}
                onchange={(e) => {
                    f.kategori = (e.currentTarget as HTMLSelectElement).value as KategoriUjian;
                    terapkanDefault(f, f.kategori);
                }}
            >
                {#each kategoriOptions as opt (opt.value)}
                    <option value={opt.value}>{opt.label}</option>
                {/each}
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" for="f-judul">Judul</label>
            <input id="f-judul" class="form-control" bind:value={f.judul} />
            {#if f.errors.judul}
                <div class="text-danger small">{f.errors.judul}</div>
            {/if}
        </div>
        <div class="col-12">
            <label class="form-label" for="f-deskripsi">Deskripsi</label>
            <textarea id="f-deskripsi" class="form-control" rows="2" bind:value={f.deskripsi}></textarea>
        </div>
        <div class="col-12">
            <label class="form-label" for="f-tata-tertib">Tata Tertib Pengerjaan</label>
            <textarea
                id="f-tata-tertib"
                class="form-control"
                rows="3"
                placeholder={"Contoh:\n1. Dilarang membuka tab/aplikasi lain.\n2. Dilarang bekerja sama.\n3. ..."}
                bind:value={f.tata_tertib}
            ></textarea>
            <div class="form-text">Ditampilkan ke siswa dan wajib disetujui sebelum ujian dimulai.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="f-mulai">Jadwal Mulai</label>
            <input id="f-mulai" type="datetime-local" class="form-control" bind:value={f.tanggal_mulai} />
            {#if f.errors.tanggal_mulai}
                <div class="text-danger small">{f.errors.tanggal_mulai}</div>
            {/if}
        </div>
        <div class="col-md-6">
            <label class="form-label" for="f-selesai">Jadwal Selesai</label>
            <input id="f-selesai" type="datetime-local" class="form-control" bind:value={f.tanggal_selesai} />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-durasi">Durasi (menit)</label>
            <input id="f-durasi" type="number" min="1" class="form-control" bind:value={f.durasi_menit} />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-attempt">Maks. Percobaan</label>
            <input id="f-attempt" type="number" min="1" class="form-control" bind:value={f.maks_attempt} />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-nilaimaks">Nilai Maks</label>
            <input id="f-nilaimaks" type="number" min="1" class="form-control" bind:value={f.nilai_maks} />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-bobot">Bobot</label>
            <input id="f-bobot" type="number" min="1" class="form-control" bind:value={f.bobot} />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-pelanggaran">Maks. Pelanggaran (0=off)</label>
            <input id="f-pelanggaran" type="number" min="0" class="form-control" bind:value={f.maks_pelanggaran} />
        </div>
        <div class="col-12 d-flex flex-wrap gap-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="f-acaksoal" bind:checked={f.acak_soal} />
                <label class="form-check-label" for="f-acaksoal">Acak soal</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="f-acakopsi" bind:checked={f.acak_opsi} />
                <label class="form-check-label" for="f-acakopsi">Acak opsi</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="f-fullscreen" bind:checked={f.wajib_fullscreen} />
                <label class="form-check-label" for="f-fullscreen">Wajib fullscreen</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="f-hasil" bind:checked={f.tampilkan_hasil} />
                <label class="form-check-label" for="f-hasil">Tampilkan hasil ke siswa</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="f-pengelola">Pengelola Token</label>
            <select id="f-pengelola" class="form-select" bind:value={f.token_pengelola}>
                <option value="guru">Guru pengampu</option>
                <option value="admin">Admin</option>
            </select>
            <div class="form-text">
                Menentukan siapa yang membuat & merilis token untuk ujian ini.
            </div>
        </div>
    </div>
{/snippet}
