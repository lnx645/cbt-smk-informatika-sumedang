<script lang="ts">
    import { router, useForm, usePage } from '@inertiajs/svelte';
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
    import { extractId } from '@/lib/utils';
    import {
        KESULITAN_INFO,
        TIPE_SOAL_INFO,
        type Kesulitan,
        type TipeSoal,
    } from '@/lib/ujian';
    import BankSoalController from '@/actions/App/Http/Controllers/Admin/BankSoalController';
    import type { PaginationMeta } from '@/types/models';

    type BankItem = {
        id: number;
        matpel_id: number;
        matpel: string | null;
        guru: string | null;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        topik: string | null;
        kesulitan: Kesulitan;
        kunci_isian: string[] | null;
        isian_case_sensitive: boolean;
        opsi: { id: number; teks: string; benar: boolean }[];
    };
    type MatpelOption = { value: number; label: string };

    let {
        bank,
        matpelOptions = [],
        filters = { matpel_id: null, tipe: null, kesulitan: null, q: '' },
    }: {
        bank: PaginationMeta & { data: BankItem[] };
        matpelOptions: MatpelOption[];
        filters: {
            matpel_id: number | null;
            tipe: TipeSoal | null;
            kesulitan: Kesulitan | null;
            q: string;
        };
    } = $props();

    const tipeOptions = (Object.keys(TIPE_SOAL_INFO) as TipeSoal[]).map((t) => ({
        value: t,
        label: TIPE_SOAL_INFO[t].label,
    }));
    const kesulitanOptions = (Object.keys(KESULITAN_INFO) as Kesulitan[]).map((k) => ({
        value: k,
        label: KESULITAN_INFO[k].label,
    }));

    function baseForm() {
        return {
            matpel_id: (matpelOptions[0]?.value ?? null) as number | null,
            tipe: 'pg' as TipeSoal,
            pertanyaan: '',
            poin: 10,
            topik: '',
            kesulitan: 'sedang' as Kesulitan,
            opsi: [
                { teks: '', benar: true },
                { teks: '', benar: false },
            ] as { teks: string; benar: boolean }[],
            kunci_isian: [''] as string[],
            isian_case_sensitive: false,
        };
    }

    const form = useForm(baseForm());
    const butuhOpsi = $derived(['pg', 'multi', 'benar_salah'].includes(form.tipe));

    // ── Filter state ─────────────────────────────────────────────────────
    let filterMatpel = $state(filters.matpel_id);
    let filterTipe: TipeSoal | null = $state(filters.tipe);
    let filterKesulitan: Kesulitan | null = $state(filters.kesulitan);
    let searchInput = $state(filters.q);
    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    // ── Grouped by matpel ─────────────────────────────────────────────────
    const grouped = $derived(() => {
        const groups: Record<string, BankItem[]> = {};
        for (const item of bank.data) {
            const key = item.matpel ?? 'Tanpa Matpel';
            if (!groups[key]) groups[key] = [];
            groups[key].push(item);
        }
        return groups;
    });

    const groupKeys = $derived(Object.keys(grouped()));
    const firstKey = $derived(groupKeys[0] ?? null);

    // ── Accordion state ──────────────────────────────────────────────────
    let openMatpel = $state<string | null>(firstKey);
    let modalOpen = $state(false);
    let editId = $state<number | null>(null);

    // ── Tipe → color ─────────────────────────────────────────────────────
    const TIPE_COLOR: Record<TipeSoal, string> = {
        pg: 'primary', multi: 'success', benar_salah: 'info', isian: 'warning', esai: 'secondary',
    };

    // ── Form actions ───────────────────────────────────────────────────────
    function resetForm() {
        form.reset();
        form.opsi = [{ teks: '', benar: true }, { teks: '', benar: false }];
        form.kunci_isian = [''];
        form.clearErrors();
    }

    function bukaBuat() { resetForm(); editId = null; modalOpen = true; }

    function bukaEdit(b: BankItem) {
        editId = b.id;
        form.matpel_id = b.matpel_id;
        form.tipe = b.tipe;
        form.pertanyaan = b.pertanyaan;
        form.poin = b.poin;
        form.topik = b.topik ?? '';
        form.kesulitan = b.kesulitan;
        form.opsi = b.opsi.length
            ? b.opsi.map((o) => ({ teks: o.teks, benar: o.benar }))
            : [{ teks: '', benar: true }, { teks: '', benar: false }];
        form.kunci_isian = b.kunci_isian?.length ? [...b.kunci_isian] : [''];
        form.isian_case_sensitive = b.isian_case_sensitive;
        form.clearErrors();
        modalOpen = true;
    }

    function gantiTipe(t: TipeSoal) {
        form.tipe = t;
        if (t === 'benar_salah') {
            form.opsi = [{ teks: 'Benar', benar: true }, { teks: 'Salah', benar: false }];
        }
    }

    function tambahOpsi() { form.opsi = [...form.opsi, { teks: '', benar: false }]; }
    function hapusOpsi(i: number) { form.opsi = form.opsi.filter((_, idx) => idx !== i); }
    function setBenarTunggal(i: number) { form.opsi = form.opsi.map((o, idx) => ({ ...o, benar: idx === i })); }
    function tambahKunci() { form.kunci_isian = [...form.kunci_isian, '']; }
    function hapusKunci(i: number) { form.kunci_isian = form.kunci_isian.filter((_, idx) => idx !== i); }

    function simpan() {
        const onSuccess = () => { modalOpen = false; resetForm(); };
        form.transform((data) => ({
            matpel_id: data.matpel_id,
            tipe: data.tipe,
            pertanyaan: data.pertanyaan,
            poin: data.poin,
            topik: data.topik,
            kesulitan: data.kesulitan,
            opsi: ['pg', 'multi', 'benar_salah'].includes(data.tipe) ? data.opsi : [],
            kunci_isian: data.tipe === 'isian' ? data.kunci_isian : [],
            isian_case_sensitive: data.isian_case_sensitive,
        }));
        if (editId) {
            form.put(BankSoalController.update({ bankSoal: editId }).url, { preserveScroll: true, onSuccess });
        } else {
            form.post(BankSoalController.store().url, { preserveScroll: true, onSuccess });
        }
    }

    async function hapus(b: BankItem) {
        const ok = await confirm.show({
            title: 'Hapus Soal Bank',
            message: 'Soal ini akan dihapus dari bank. Ujian yang sudah memakai salinannya tidak terpengaruh. Lanjutkan?',
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(BankSoalController.destroy({ bankSoal: b.id }).url, { preserveScroll: true });
    }

    // ── Filter actions ───────────────────────────────────────────────────
    function baseUrl() { return (usePage().url as string).split('?')[0]; }
    function buildParams(page?: number) {
        return {
            ...(page !== undefined ? { page } : {}),
            matpel_id: filterMatpel ?? undefined,
            tipe: filterTipe ?? undefined,
            kesulitan: filterKesulitan ?? undefined,
            q: searchInput.trim() || undefined,
        };
    }
    function reload() {
        router.get(baseUrl(), buildParams(), { preserveState: true, preserveScroll: true, replace: true, only: ['bank', 'filters'] });
    }
    function goToPage(page: number) {
        router.get(baseUrl(), buildParams(page), { preserveState: true, preserveScroll: true, replace: true, only: ['bank'] });
    }
    function onSearchInput() { clearTimeout(searchTimer); searchTimer = setTimeout(reload, 400); }
</script>

<div class="container-fluid px-0">
    <PageHeader title="Bank Soal" subtitle="Kelola seluruh bank soal lintas mata pelajaran.">
        {#snippet actions()}
            <Button color="primary" onclick={bukaBuat}>
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </Button>
        {/snippet}
    </PageHeader>

    <!-- ── Filter Bar ─────────────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div style="min-width:180px">
            <Select
                items={matpelOptions}
                value={filterMatpel}
                placeholder="Semua matpel"
                clearable={true}
                getOptionValue={(item) => item.value}
                onchange={(v) => { filterMatpel = extractId(v); reload(); }}
            />
        </div>
        <div style="min-width:150px">
            <Select
                items={tipeOptions}
                value={filterTipe}
                placeholder="Semua tipe"
                clearable={true}
                getOptionValue={(item) => item.value}
                onchange={(v) => { filterTipe = (v as {value: TipeSoal} | null)?.value ?? null; reload(); }}
            />
        </div>
        <div style="min-width:140px">
            <Select
                items={kesulitanOptions}
                value={filterKesulitan}
                placeholder="Semua tingkat"
                clearable={true}
                getOptionValue={(item) => item.value}
                onchange={(v) => { filterKesulitan = (v as {value: Kesulitan} | null)?.value ?? null; reload(); }}
            />
        </div>
        <div class="input-group input-group-sm ms-auto" style="max-width:220px">
            <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control" placeholder="Cari…" bind:value={searchInput} onkeyup={onSearchInput} />
        </div>
    </div>

    <!-- ── Main Card ─────────────────────────────────────────────────── -->
    <Card>
        <CardBody class="p-0">

            {#if bank.data.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inboxes display-5 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold mb-1">Belum ada soal di bank.</div>
                    <div class="small mb-3">Tambahkan soal baru untuk mulai membangun bank soal.</div>
                    <Button color="primary" onclick={bukaBuat}>
                        <i class="bi bi-plus-lg me-1"></i>Tambah Soal Pertama
                    </Button>
                </div>

            {:else}
                <!-- Toolbar -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <span class="small text-muted">{groupKeys.length} mata pelajaran · {bank.total} soal</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary" onclick={() => { openMatpel = null; }}>
                            <i class="bi bi-arrows-collapse me-1"></i>Tutup
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick={() => { openMatpel = firstKey; }}>
                            <i class="bi bi-arrows-expand me-1"></i>Buka
                        </button>
                    </div>
                </div>

                <!-- Grouped by Matpel -->
                {#each groupKeys as matpelName (matpelName)}
                    {@const items = grouped()[matpelName]}
                    {@const isOpen = openMatpel === matpelName}
                    {@const totalPoin = items.reduce((s, b) => s + b.poin, 0)}
                    {@const tipeCount = items.reduce((acc, b) => {
                        acc[b.tipe] = (acc[b.tipe] ?? 0) + 1;
                        return acc;
                    }, {} as Record<string, number>)}

                    <!-- Matpel Header -->
                    <div
                        class="d-flex align-items-center gap-2 px-3 py-2 border-bottom"
                        role="button"
                        tabindex="0"
                        onclick={() => { openMatpel = isOpen ? null : matpelName; }}
                        onkeydown={(e) => e.key === 'Enter' && (openMatpel = isOpen ? null : matpelName)}
                        style="cursor:pointer"
                    >
                        <i class={`bi ${isOpen ? 'bi-chevron-down' : 'bi-chevron-right'} text-muted flex-shrink-0`}></i>
                        <i class="bi bi-book text-primary flex-shrink-0"></i>
                        <span class="fw-semibold flex-grow-1 small">{matpelName}</span>
                        {#each Object.entries(tipeCount) as [tipe, count] (tipe)}
                            <span class={`badge bg-light text-dark border`} style="font-size:0.63rem">
                                {TIPE_SOAL_INFO[tipe as TipeSoal]?.label ?? tipe}: {count}
                            </span>
                        {/each}
                        <span class="badge bg-dark" style="font-size:0.63rem">{items.length} · {totalPoin}pt</span>
                    </div>

                    <!-- Question Rows -->
                    <Collapse isOpen={isOpen}>
                        <div>
                            {#each items as b (b.id)}
                                <div class="d-flex align-items-start gap-3 px-3 py-2 border-bottom">
                                    <!-- Left accent bar -->
                                    <div class={`rounded flex-shrink-0 accent-bar accent-${TIPE_COLOR[b.tipe]}`}></div>

                                    <!-- Info -->
                                    <div class="flex-grow-1 min-w-0">
                                        <!-- Meta -->
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <Badge color={TIPE_COLOR[b.tipe]} class="text-white" style="font-size:0.68rem">
                                                <i class={`bi ${TIPE_SOAL_INFO[b.tipe].icon} me-1`}></i>
                                                {TIPE_SOAL_INFO[b.tipe].label}
                                            </Badge>
                                            <Badge color={KESULITAN_INFO[b.kesulitan].color} style="font-size:0.63rem">
                                                {KESULITAN_INFO[b.kesulitan].label}
                                            </Badge>
                                            <span class="text-muted small">{b.poin} poin</span>
                                            {#if b.guru}
                                                <span class="badge bg-light text-dark border" style="font-size:0.63rem">{b.guru}</span>
                                            {/if}
                                            {#if b.topik}
                                                <span class="badge bg-light text-dark border" style="font-size:0.63rem">{b.topik}</span>
                                            {/if}
                                        </div>

                                        <!-- Pertanyaan -->
                                        <div class="mb-1" style="font-size:0.875rem">{@html b.pertanyaan}</div>

                                        <!-- Jawaban -->
                                        {#if b.opsi.length}
                                            <div class="d-flex flex-wrap gap-1">
                                                {#each b.opsi as o (o.id)}
                                                    <span class={`badge ${o.benar ? `bg-${TIPE_COLOR[b.tipe]}` : 'bg-light text-dark border'}`} style="font-size:0.7rem">
                                                        {#if o.benar}<i class="bi bi-check-circle-fill me-1"></i>{:else}<i class="bi bi-circle me-1" style="font-size:0.6rem"></i>{/if}{o.teks}
                                                    </span>
                                                {/each}
                                            </div>
                                        {:else if b.kunci_isian?.length}
                                            <div class="small text-muted">
                                                <i class="bi bi-key me-1"></i>{b.kunci_isian.join(' | ')}
                                            </div>
                                        {/if}
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-inline-flex gap-1 flex-shrink-0">
                                        <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(b)} title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </Button>
                                        <Button size="sm" color="outline-danger" onclick={() => hapus(b)} title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </Button>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </Collapse>
                {/each}

                <!-- Pagination -->
                <div class="px-3 py-2 border-top">
                    <Pagination meta={bank} onPageChange={goToPage} />
                </div>
            {/if}
        </CardBody>
    </Card>

    <!-- ── Modal Form ─────────────────────────────────────────────────── -->
    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
        <ModalHeader toggle={() => (modalOpen = false)}>
            <i class={`bi ${editId ? 'bi-pencil-square' : 'bi-plus-circle'} me-2`}></i>
            {editId ? 'Edit Soal' : 'Tambah Soal'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="b-matpel">Mata Pelajaran</label>
                    <Select
                        id="b-matpel"
                        items={matpelOptions}
                        value={form.matpel_id}
                        placeholder="Pilih matpel"
                        getOptionValue={(item) => item.value}
                        onchange={(v) => (form.matpel_id = extractId(v))}
                    />
                    {#if form.errors.matpel_id}<div class="text-danger small">{form.errors.matpel_id}</div>{/if}
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="b-tipe">Tipe</label>
                    <select id="b-tipe" class="form-select" value={form.tipe} onchange={(e) => gantiTipe((e.currentTarget as HTMLSelectElement).value as TipeSoal)}>
                        {#each tipeOptions as opt (opt.value)}<option value={opt.value}>{opt.label}</option>{/each}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="b-poin">Poin</label>
                    <input id="b-poin" type="number" min="1" class="form-control" bind:value={form.poin} />
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="b-topik">Topik (opsional)</label>
                    <input id="b-topik" class="form-control" bind:value={form.topik} />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="b-kesulitan">Kesulitan</label>
                    <select id="b-kesulitan" class="form-select" bind:value={form.kesulitan}>
                        {#each kesulitanOptions as opt (opt.value)}<option value={opt.value}>{opt.label}</option>{/each}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="b-pertanyaan">Pertanyaan</label>
                    <textarea id="b-pertanyaan" class="form-control" rows="3" bind:value={form.pertanyaan}></textarea>
                    {#if form.errors.pertanyaan}<div class="text-danger small">{form.errors.pertanyaan}</div>{/if}
                </div>

                {#if butuhOpsi}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Opsi Jawaban</span>
                            {#if form.tipe !== 'benar_salah'}
                                <Button size="sm" color="outline-primary" onclick={tambahOpsi}><i class="bi bi-plus"></i> Opsi</Button>
                            {/if}
                        </div>
                        {#each form.opsi as _, i (i)}
                            <div class="input-group mb-2">
                                <span class="input-group-text">
                                    {#if form.tipe === 'multi'}
                                        <input type="checkbox" checked={form.opsi[i].benar} onchange={(e) => (form.opsi[i].benar = (e.currentTarget as HTMLInputElement).checked)} />
                                    {:else}
                                        <input type="radio" name="bank-opsi" checked={form.opsi[i].benar} onchange={() => setBenarTunggal(i)} />
                                    {/if}
                                </span>
                                <input class="form-control" placeholder={`Opsi ${i + 1}`} bind:value={form.opsi[i].teks} readonly={form.tipe === 'benar_salah'} />
                                {#if form.tipe !== 'benar_salah' && form.opsi.length > 2}
                                    <Button color="outline-danger" onclick={() => hapusOpsi(i)}><i class="bi bi-x"></i></Button>
                                {/if}
                            </div>
                        {/each}
                        {#if form.errors.opsi}<div class="text-danger small">{form.errors.opsi}</div>{/if}
                    </div>
                {:else if form.tipe === 'isian'}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="form-label mb-0">Kunci Jawaban</span>
                            <Button size="sm" color="outline-primary" onclick={tambahKunci}><i class="bi bi-plus"></i> Kunci</Button>
                        </div>
                        {#each form.kunci_isian as _, i (i)}
                            <div class="input-group mb-2">
                                <input class="form-control" bind:value={form.kunci_isian[i]} placeholder={`Kunci ${i + 1}`} />
                                {#if form.kunci_isian.length > 1}
                                    <Button color="outline-danger" onclick={() => hapusKunci(i)}><i class="bi bi-x"></i></Button>
                                {/if}
                            </div>
                        {/each}
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="b-case" bind:checked={form.isian_case_sensitive} />
                            <label class="form-check-label" for="b-case">Perhatikan huruf besar/kecil</label>
                        </div>
                        {#if form.errors.kunci_isian}<div class="text-danger small">{form.errors.kunci_isian}</div>{/if}
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
            <Button color="primary" onclick={simpan} disabled={form.processing}>
                <i class="bi bi-check-lg me-1"></i>Simpan
            </Button>
        </ModalFooter>
    </Modal>
</div>

<style>
    .accent-bar {
        width: 4px;
        min-height: 36px;
    }
    .accent-primary   { background: var(--bs-primary); }
    .accent-success   { background: var(--bs-success); }
    .accent-warning   { background: var(--bs-warning); }
    .accent-info      { background: var(--bs-info); }
    .accent-secondary { background: var(--bs-secondary); }
</style>
