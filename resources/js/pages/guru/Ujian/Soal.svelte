<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { inertia } from '@inertiajs/svelte';
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
    import { confirm } from '@/lib/confirm.svelte';
    import {
        KESULITAN_INFO,
        TIPE_SOAL_INFO,
        type Kesulitan,
        type TipeSoal,
    } from '@/lib/ujian';
    import SoalController from '@/actions/App/Http/Controllers/Guru/SoalController';
    import UjianController from '@/actions/App/Http/Controllers/Guru/UjianController';

    type Opsi = { id?: number; teks: string; benar: boolean };
    type BankItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        topik: string | null;
        kesulitan: Kesulitan;
        jumlah_opsi: number;
    };
    type SoalItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        urutan: number;
        kunci_isian: string[] | null;
        isian_case_sensitive: boolean;
        opsi: { id: number; teks: string; benar: boolean }[];
    };

    let {
        ujian,
        soals,
        bank = [],
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: string;
            status: string;
            kelas: string | null;
            matpel: string | null;
            matpel_id: number | null;
            nilai_maks: number;
            total_poin: number;
        };
        soals: SoalItem[];
        bank: BankItem[];
    } = $props();

    const tipeOptions = (Object.keys(TIPE_SOAL_INFO) as TipeSoal[]).map((t) => ({
        value: t,
        label: TIPE_SOAL_INFO[t].label,
    }));

    const form = useForm({
        tipe: 'pg' as TipeSoal,
        pertanyaan: '',
        poin: 1,
        opsi: [
            { teks: '', benar: true },
            { teks: '', benar: false },
        ] as Opsi[],
        kunci_isian: [''] as string[],
        isian_case_sensitive: false,
    });

    let modalOpen = $state(false);
    let editId = $state<number | null>(null);

    function resetForm() {
        form.reset();
        form.opsi = [
            { teks: '', benar: true },
            { teks: '', benar: false },
        ];
        form.kunci_isian = [''];
        form.clearErrors();
    }

    function bukaBuat() {
        resetForm();
        editId = null;
        modalOpen = true;
    }

    function bukaEdit(s: SoalItem) {
        editId = s.id;
        form.tipe = s.tipe;
        form.pertanyaan = s.pertanyaan;
        form.poin = s.poin;
        form.opsi = s.opsi.length
            ? s.opsi.map((o) => ({ teks: o.teks, benar: o.benar }))
            : [
                  { teks: '', benar: true },
                  { teks: '', benar: false },
              ];
        form.kunci_isian = s.kunci_isian?.length ? [...s.kunci_isian] : [''];
        form.isian_case_sensitive = s.isian_case_sensitive;
        form.clearErrors();
        modalOpen = true;
    }

    function gantiTipe(t: TipeSoal) {
        form.tipe = t;
        if (t === 'benar_salah') {
            form.opsi = [
                { teks: 'Benar', benar: true },
                { teks: 'Salah', benar: false },
            ];
        }
    }

    function tambahOpsi() {
        form.opsi = [...form.opsi, { teks: '', benar: false }];
    }
    function hapusOpsi(i: number) {
        form.opsi = form.opsi.filter((_, idx) => idx !== i);
    }
    function setBenarTunggal(i: number) {
        form.opsi = form.opsi.map((o, idx) => ({ ...o, benar: idx === i }));
    }
    function tambahKunci() {
        form.kunci_isian = [...form.kunci_isian, ''];
    }
    function hapusKunci(i: number) {
        form.kunci_isian = form.kunci_isian.filter((_, idx) => idx !== i);
    }

    function simpan() {
        const onSuccess = () => {
            modalOpen = false;
            resetForm();
        };
        const butuhOpsiTipe = ['pg', 'multi', 'benar_salah'].includes(
            form.tipe,
        );
        form.transform((data) => ({
            tipe: data.tipe,
            pertanyaan: data.pertanyaan,
            poin: data.poin,
            opsi: butuhOpsiTipe ? data.opsi : [],
            kunci_isian: data.tipe === 'isian' ? data.kunci_isian : [],
            isian_case_sensitive: data.isian_case_sensitive,
        }));
        if (editId) {
            form.put(SoalController.update({ ujian: ujian.id, soal: editId }).url, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post(SoalController.store({ ujian: ujian.id }).url, {
                preserveScroll: true,
                onSuccess,
            });
        }
    }

    async function hapus(s: SoalItem) {
        const ok = await confirm.show({
            title: 'Hapus Soal',
            message: 'Soal ini akan dihapus. Lanjutkan?',
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(SoalController.destroy({ ujian: ujian.id, soal: s.id }).url, {
            preserveScroll: true,
        });
    }

    const butuhOpsi = $derived(['pg', 'multi', 'benar_salah'].includes(form.tipe));

    // ---- Bank soal ----
    let bankModalOpen = $state(false);
    let generateModalOpen = $state(false);
    let simpanBankOpen = $state(false);
    let simpanBankSoal = $state<SoalItem | null>(null);
    let bankTerpilih = $state<number[]>([]);

    const ambilForm = useForm({ bank_soal_ids: [] as number[] });
    const generateForm = useForm({
        jumlah: 5,
        tipe: '' as '' | TipeSoal,
        topik: '',
        kesulitan: '' as '' | Kesulitan,
    });
    const simpanForm = useForm({ topik: '', kesulitan: 'sedang' as Kesulitan });

    function toggleBank(id: number) {
        bankTerpilih = bankTerpilih.includes(id)
            ? bankTerpilih.filter((x) => x !== id)
            : [...bankTerpilih, id];
    }

    function ambilDariBank() {
        ambilForm.bank_soal_ids = bankTerpilih;
        ambilForm.post(SoalController.ambilDariBank({ ujian: ujian.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                bankModalOpen = false;
                bankTerpilih = [];
            },
        });
    }

    function generate() {
        generateForm
            .transform((d) => ({
                jumlah: d.jumlah,
                tipe: d.tipe || null,
                topik: d.topik || null,
                kesulitan: d.kesulitan || null,
            }))
            .post(SoalController.generateDariBank({ ujian: ujian.id }).url, {
                preserveScroll: true,
                onSuccess: () => (generateModalOpen = false),
            });
    }

    function bukaSimpanBank(s: SoalItem) {
        simpanBankSoal = s;
        simpanForm.reset();
        simpanForm.clearErrors();
        simpanBankOpen = true;
    }

    function simpanKeBank() {
        if (!simpanBankSoal) return;
        simpanForm.post(
            SoalController.simpanKeBank({ ujian: ujian.id, soal: simpanBankSoal.id }).url,
            {
                preserveScroll: true,
                onSuccess: () => (simpanBankOpen = false),
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Daftar Ujian
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">{ujian.judul}</h1>
            <div class="text-muted small">
                {ujian.kelas} · {ujian.matpel} · Total poin: {ujian.total_poin}
            </div>
        </div>
        <Button color="primary" onclick={bukaBuat}>
            <i class="bi bi-plus-lg me-1"></i>Tambah Soal
        </Button>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <Button color="outline-primary" size="sm" onclick={() => (bankModalOpen = true)}>
            <i class="bi bi-collection me-1"></i>Ambil dari Bank
        </Button>
        <Button color="outline-primary" size="sm" onclick={() => (generateModalOpen = true)}>
            <i class="bi bi-shuffle me-1"></i>Generate Acak
        </Button>
    </div>

    {#if soals.length === 0}
        <div class="text-center text-muted py-5">
            <i class="bi bi-card-list display-5 d-block mb-2"></i>
            <div>Belum ada soal. Tambahkan soal untuk ujian ini.</div>
        </div>
    {:else}
        {#each soals as s, i (s.id)}
            <Card class="border rounded-1 shadow-none mb-2">
                <CardBody class="p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold">Soal {i + 1}</span>
                                <Badge color="light" class="text-dark border">
                                    <i class={`bi ${TIPE_SOAL_INFO[s.tipe].icon} me-1`}></i>
                                    {TIPE_SOAL_INFO[s.tipe].label}
                                </Badge>
                                <span class="text-muted small">{s.poin} poin</span>
                            </div>
                            <div class="rich-deskripsi">{@html s.pertanyaan}</div>
                            {#if s.opsi.length}
                                <ul class="mt-2 mb-0 small">
                                    {#each s.opsi as o (o.id)}
                                        <li class={o.benar ? 'text-success fw-semibold' : ''}>
                                            {o.teks}
                                            {#if o.benar}<i class="bi bi-check-circle ms-1"></i>{/if}
                                        </li>
                                    {/each}
                                </ul>
                            {:else if s.kunci_isian?.length}
                                <div class="small text-muted mt-2">
                                    Kunci: {s.kunci_isian.join(', ')}
                                </div>
                            {/if}
                        </div>
                        <div class="d-inline-flex gap-1">
                            <Button size="sm" color="outline-primary" onclick={() => bukaSimpanBank(s)} title="Simpan ke bank">
                                <i class="bi bi-bookmark-plus"></i>
                            </Button>
                            <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(s)}>
                                <i class="bi bi-pencil"></i>
                            </Button>
                            <Button size="sm" color="outline-danger" onclick={() => hapus(s)}>
                                <i class="bi bi-trash"></i>
                            </Button>
                        </div>
                    </div>
                </CardBody>
            </Card>
        {/each}
    {/if}

    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>
            {editId ? 'Edit Soal' : 'Tambah Soal'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="s-tipe">Tipe Soal</label>
                    <select
                        id="s-tipe"
                        class="form-select"
                        value={form.tipe}
                        onchange={(e) => gantiTipe((e.currentTarget as HTMLSelectElement).value as TipeSoal)}
                    >
                        {#each tipeOptions as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="s-poin">Poin</label>
                    <input id="s-poin" type="number" min="1" class="form-control" bind:value={form.poin} />
                </div>
                <div class="col-12">
                    <label class="form-label" for="s-pertanyaan">Pertanyaan</label>
                    <textarea id="s-pertanyaan" class="form-control" rows="3" bind:value={form.pertanyaan}></textarea>
                    {#if form.errors.pertanyaan}
                        <div class="text-danger small">{form.errors.pertanyaan}</div>
                    {/if}
                </div>

                {#if butuhOpsi}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Opsi Jawaban</span>
                            {#if form.tipe !== 'benar_salah'}
                                <Button size="sm" color="outline-primary" onclick={tambahOpsi}>
                                    <i class="bi bi-plus"></i> Opsi
                                </Button>
                            {/if}
                        </div>
                        {#each form.opsi as opsi, i (i)}
                            <div class="input-group mb-2">
                                <span class="input-group-text">
                                    {#if form.tipe === 'multi'}
                                        <input
                                            type="checkbox"
                                            checked={opsi.benar}
                                            onchange={(e) => (form.opsi[i].benar = (e.currentTarget as HTMLInputElement).checked)}
                                        />
                                    {:else}
                                        <input
                                            type="radio"
                                            name="opsi-benar"
                                            checked={opsi.benar}
                                            onchange={() => setBenarTunggal(i)}
                                        />
                                    {/if}
                                </span>
                                <input
                                    class="form-control"
                                    placeholder={`Opsi ${i + 1}`}
                                    bind:value={form.opsi[i].teks}
                                    readonly={form.tipe === 'benar_salah'}
                                />
                                {#if form.tipe !== 'benar_salah' && form.opsi.length > 2}
                                    <Button color="outline-danger" onclick={() => hapusOpsi(i)}>
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        {#if form.errors.opsi}
                            <div class="text-danger small">{form.errors.opsi}</div>
                        {/if}
                    </div>
                {:else if form.tipe === 'isian'}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Kunci Jawaban (salah satu cocok = benar)</span>
                            <Button size="sm" color="outline-primary" onclick={tambahKunci}>
                                <i class="bi bi-plus"></i> Kunci
                            </Button>
                        </div>
                        {#each form.kunci_isian as _, i (i)}
                            <div class="input-group mb-2">
                                <input class="form-control" bind:value={form.kunci_isian[i]} placeholder={`Kunci ${i + 1}`} />
                                {#if form.kunci_isian.length > 1}
                                    <Button color="outline-danger" onclick={() => hapusKunci(i)}>
                                        <i class="bi bi-x"></i>
                                    </Button>
                                {/if}
                            </div>
                        {/each}
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="s-case" bind:checked={form.isian_case_sensitive} />
                            <label class="form-check-label" for="s-case">Perhatikan huruf besar/kecil</label>
                        </div>
                        {#if form.errors.kunci_isian}
                            <div class="text-danger small">{form.errors.kunci_isian}</div>
                        {/if}
                    </div>
                {:else}
                    <div class="col-12">
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Soal esai dinilai manual oleh guru setelah ujian.
                        </div>
                    </div>
                {/if}
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpan} disabled={form.processing}>Simpan</Button>
        </ModalFooter>
    </Modal>

    <!-- Ambil dari Bank -->
    <Modal isOpen={bankModalOpen} toggle={() => (bankModalOpen = !bankModalOpen)} size="lg">
        <ModalHeader toggle={() => (bankModalOpen = false)}>Ambil Soal dari Bank</ModalHeader>
        <ModalBody>
            {#if bank.length === 0}
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inboxes display-6 d-block mb-2"></i>
                    Belum ada soal bank untuk mata pelajaran ini.
                </div>
            {:else}
                <div class="small text-muted mb-2">
                    {bankTerpilih.length} soal dipilih. Soal akan disalin (snapshot) ke ujian.
                </div>
                {#each bank as b (b.id)}
                    <label class="d-flex align-items-start gap-2 border rounded-1 p-2 mb-2">
                        <input
                            type="checkbox"
                            class="mt-1"
                            checked={bankTerpilih.includes(b.id)}
                            onchange={() => toggleBank(b.id)}
                        />
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <Badge color="light" class="text-dark border">{TIPE_SOAL_INFO[b.tipe].label}</Badge>
                                <Badge color={KESULITAN_INFO[b.kesulitan].color} pill>{KESULITAN_INFO[b.kesulitan].label}</Badge>
                                <span class="text-muted small">{b.poin} poin</span>
                                {#if b.topik}<span class="badge bg-secondary-subtle text-secondary-emphasis">{b.topik}</span>{/if}
                            </div>
                            <div class="rich-deskripsi small">{@html b.pertanyaan}</div>
                        </div>
                    </label>
                {/each}
            {/if}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (bankModalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={ambilDariBank} disabled={ambilForm.processing || bankTerpilih.length === 0}>
                Salin {bankTerpilih.length} Soal
            </Button>
        </ModalFooter>
    </Modal>

    <!-- Generate Acak -->
    <Modal isOpen={generateModalOpen} toggle={() => (generateModalOpen = !generateModalOpen)}>
        <ModalHeader toggle={() => (generateModalOpen = false)}>Generate Acak dari Bank</ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label" for="g-jumlah">Jumlah Soal</label>
                    <input id="g-jumlah" type="number" min="1" class="form-control" bind:value={generateForm.jumlah} />
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-tipe">Tipe (opsional)</label>
                    <select id="g-tipe" class="form-select" bind:value={generateForm.tipe}>
                        <option value="">Semua</option>
                        {#each Object.keys(TIPE_SOAL_INFO) as t (t)}
                            <option value={t}>{TIPE_SOAL_INFO[t as TipeSoal].label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-topik">Topik (opsional)</label>
                    <input id="g-topik" class="form-control" bind:value={generateForm.topik} />
                </div>
                <div class="col-6">
                    <label class="form-label" for="g-kesulitan">Kesulitan (opsional)</label>
                    <select id="g-kesulitan" class="form-select" bind:value={generateForm.kesulitan}>
                        <option value="">Semua</option>
                        {#each Object.keys(KESULITAN_INFO) as k (k)}
                            <option value={k}>{KESULITAN_INFO[k as Kesulitan].label}</option>
                        {/each}
                    </select>
                </div>
            </div>
            <div class="form-text mt-2">
                Sistem mengambil soal acak dari bank matpel ini sesuai kriteria, lalu menyalinnya ke ujian.
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (generateModalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={generate} disabled={generateForm.processing}>Generate</Button>
        </ModalFooter>
    </Modal>

    <!-- Simpan ke Bank -->
    <Modal isOpen={simpanBankOpen} toggle={() => (simpanBankOpen = !simpanBankOpen)}>
        <ModalHeader toggle={() => (simpanBankOpen = false)}>Simpan Soal ke Bank</ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="sb-topik">Topik (opsional)</label>
                    <input id="sb-topik" class="form-control" bind:value={simpanForm.topik} placeholder="mis. Aljabar" />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sb-kesulitan">Kesulitan</label>
                    <select id="sb-kesulitan" class="form-select" bind:value={simpanForm.kesulitan}>
                        {#each Object.keys(KESULITAN_INFO) as k (k)}
                            <option value={k}>{KESULITAN_INFO[k as Kesulitan].label}</option>
                        {/each}
                    </select>
                </div>
            </div>
            <div class="form-text mt-2">Soal disalin ke bank mata pelajaran ujian ini.</div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (simpanBankOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpanKeBank} disabled={simpanForm.processing}>Simpan ke Bank</Button>
        </ModalFooter>
    </Modal>
</div>
