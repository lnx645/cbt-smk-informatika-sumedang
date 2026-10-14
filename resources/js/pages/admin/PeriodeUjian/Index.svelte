<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
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
    import { confirm } from '@/lib/confirm.svelte';
    import { KATEGORI_INFO, type KategoriUjian } from '@/lib/ujian';
    import PeriodeUjianController from '@/actions/App/Http/Controllers/Admin/PeriodeUjianController';

    type PeriodeItem = {
        id: number;
        kategori: KategoriUjian;
        nama: string;
        tahun_ajaran: string | null;
        tahun_ajaran_id: number;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
    };
    type TahunAjaran = { id: number; name: string; active: boolean };

    let {
        periode,
        tahunAjaran,
    }: {
        periode: PeriodeItem[];
        tahunAjaran: TahunAjaran[];
    } = $props();

    const KATEGORI_LIST: KategoriUjian[] = ['uts', 'uas', 'usbk'];

    const form = useForm({
        kategori: 'uts' as KategoriUjian,
        tahun_ajaran_id: tahunAjaran.find((t) => t.active)?.id ?? tahunAjaran[0]?.id ?? null,
        nama: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    let modalOpen = $state(false);
    let editId = $state<number | null>(null);
    let openKategori = $state<KategoriUjian | null>('uts');

    // ── Stats ────────────────────────────────────────────────────────────
    const now = Date.now();
    const stats = $derived({
        total: periode.length,
        aktif: periode.filter(p => {
            const s = p.tanggal_mulai ? new Date(p.tanggal_mulai).getTime() : null;
            const e = p.tanggal_selesai ? new Date(p.tanggal_selesai).getTime() : null;
            return s !== null && now >= s && (e === null || now <= e);
        }).length,
        akanDatang: periode.filter(p => {
            const s = p.tanggal_mulai ? new Date(p.tanggal_mulai).getTime() : null;
            return s !== null && now < s;
        }).length,
        selesai: periode.filter(p => {
            const e = p.tanggal_selesai ? new Date(p.tanggal_selesai).getTime() : null;
            return e !== null && now > e;
        }).length,
    });

    // ── Grouped ─────────────────────────────────────────────────────────
    const grouped = $derived(
        Object.fromEntries(
            KATEGORI_LIST.map((k) => [k, periode.filter((p) => p.kategori === k)]),
        ) as Record<KategoriUjian, PeriodeItem[]>,
    );

    function fmt(d: Date | null, opts?: Intl.DateTimeFormatOptions) {
        if (!d) return null;
        return d.toLocaleDateString('id-ID', opts ?? { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function durasi(d1: Date | null, d2: Date | null): number | null {
        if (!d1 || !d2) return null;
        return Math.ceil((d2.getTime() - d1.getTime()) / 86400000);
    }

    function statusOf(p: PeriodeItem): { label: string; color: string; icon: string } {
        const s = p.tanggal_mulai ? new Date(p.tanggal_mulai).getTime() : null;
        const e = p.tanggal_selesai ? new Date(p.tanggal_selesai).getTime() : null;
        if (!s) return { label: 'Tanpa jadwal', color: 'secondary', icon: 'bi-question-circle' };
        if (now < s) {
            const days = Math.ceil((s - now) / 86400000);
            return { label: `${days}h lagi`, color: 'info', icon: 'bi-clock-history' };
        }
        if (!e || now <= e) return { label: 'Aktif', color: 'success', icon: 'bi-broadcast' };
        return { label: 'Selesai', color: 'secondary', icon: 'bi-check-circle' };
    }

    // ── Actions ──────────────────────────────────────────────────────────
    function bukaBuat() {
        form.reset();
        form.tahun_ajaran_id = tahunAjaran.find((t) => t.active)?.id ?? tahunAjaran[0]?.id ?? null;
        editId = null;
        form.clearErrors();
        modalOpen = true;
    }

    function bukaEdit(p: PeriodeItem) {
        editId = p.id;
        form.kategori = p.kategori;
        form.tahun_ajaran_id = p.tahun_ajaran_id;
        form.nama = p.nama;
        form.tanggal_mulai = p.tanggal_mulai ?? '';
        form.tanggal_selesai = p.tanggal_selesai ?? '';
        form.clearErrors();
        modalOpen = true;
    }

    function simpan() {
        const onSuccess = () => { modalOpen = false; form.reset(); };
        if (editId) {
            form.put(PeriodeUjianController.update({ periodeUjian: editId }).url, { onSuccess });
        } else {
            form.post(PeriodeUjianController.store().url, { onSuccess });
        }
    }

    async function hapus(p: PeriodeItem) {
        const ok = await confirm.show({
            title: 'Hapus Periode',
            message: `Periode "${p.nama}" akan dihapus. Ujian terkait tidak ikut terhapus. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(PeriodeUjianController.destroy({ periodeUjian: p.id }).url);
    }

    // Kategori → badge color (Bootstrap color name)
    const KAT_COLOR: Record<KategoriUjian, string> = {
        kuis: 'info', uts: 'primary', uas: 'warning', usbk: 'danger',
    };
</script>

<div class="container-fluid px-0">
    <PageHeader title="Periode Ujian" subtitle="Tetapkan jendela jadwal UTS/UAS/USBK.">
        {#snippet actions()}
            <Button color="primary" onclick={bukaBuat}>
                <i class="bi bi-plus-lg me-1"></i>Tambah
            </Button>
        {/snippet}
    </PageHeader>

    <!-- ── Stats Row ─────────────────────────────────────────────── -->
    {#if periode.length > 0}
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
                        <div class="text-success text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Aktif</div>
                        <div class="fw-bold text-success" style="font-size:1.5rem;line-height:1">{stats.aktif}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card border-info">
                    <div class="card-body p-2 text-center">
                        <div class="text-info text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Akan Datang</div>
                        <div class="fw-bold text-info" style="font-size:1.5rem;line-height:1">{stats.akanDatang}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card border-secondary">
                    <div class="card-body p-2 text-center">
                        <div class="text-secondary text-uppercase fw-semibold" style="font-size:0.6rem;letter-spacing:0.06em">Selesai</div>
                        <div class="fw-bold text-secondary" style="font-size:1.5rem;line-height:1">{stats.selesai}</div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    <!-- ── Main ─────────────────────────────────────────────────── -->
    <Card>
        <CardBody class="p-0">

            {#if periode.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x display-5 d-block mb-2 opacity-25"></i>
                    <div class="fw-semibold mb-1">Belum ada periode ujian.</div>
                    <div class="small mb-3">Tambahkan periode agar guru bisa menjadwalkan UTS, UAS, atau USBK.</div>
                    <Button color="primary" onclick={bukaBuat}>
                        <i class="bi bi-plus-lg me-1"></i>Tambah Periode Pertama
                    </Button>
                </div>

            {:else}
                <!-- Kategori accordion -->
                {#each KATEGORI_LIST as kategori (kategori)}
                    {@const items = grouped[kategori]}
                    {@const isOpen = openKategori === kategori}

                    <!-- Kategori Header -->
                    <div
                        class="d-flex align-items-center gap-2 px-3 py-2 border-bottom"
                        role="button"
                        tabindex="0"
                        onclick={() => { openKategori = isOpen ? null : kategori; }}
                        onkeydown={(e) => e.key === 'Enter' && (openKategori = isOpen ? null : kategori)}
                        style="cursor:pointer"
                    >
                        <i class={`bi ${isOpen ? 'bi-chevron-down' : 'bi-chevron-right'} text-muted flex-shrink-0`}></i>
                        <Badge color={KAT_COLOR[kategori]}>{KATEGORI_INFO[kategori].label}</Badge>
                        <span class="small text-muted flex-grow-1">{items.length} periode</span>
                    </div>

                    <!-- Items -->
                    <Collapse isOpen={isOpen}>
                        <div>
                            {#if items.length === 0}
                                <div class="text-muted small fst-italic px-3 py-2">Belum ada periode {KATEGORI_INFO[kategori].label}.</div>
                            {:else}
                                {#each items as p (p.id)}
                                    {@const mulai = p.tanggal_mulai ? new Date(p.tanggal_mulai) : null}
                                    {@const selesai = p.tanggal_selesai ? new Date(p.tanggal_selesai) : null}
                                    {@const st = statusOf(p)}
                                    {@const hari = durasi(mulai, selesai)}

                                    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                                        <!-- Left accent bar -->
                                        <div class={`rounded flex-shrink-0 accent-bar accent-${KAT_COLOR[kategori]}`}></div>

                                        <!-- Info -->
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="fw-semibold" style="font-size:0.875rem">{p.nama}</span>
                                                {#if p.tahun_ajaran}
                                                    <span class="badge bg-light text-dark border" style="font-size:0.65rem">{p.tahun_ajaran}</span>
                                                {/if}
                                                <Badge color={st.color} style="font-size:0.63rem">
                                                    <i class={`bi ${st.icon} me-1`}></i>{st.label}
                                                </Badge>
                                            </div>
                                            <div class="small text-muted mt-0.5">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                {fmt(mulai) ?? '—'} → {fmt(selesai) ?? '—'}
                                                {#if hari !== null}<span class="ms-2"><i class="bi bi-clock me-1"></i>{hari} hari</span>{/if}
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-inline-flex gap-1 flex-shrink-0">
                                            <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(p)} title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </Button>
                                            <Button size="sm" color="outline-danger" onclick={() => hapus(p)} title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </Button>
                                        </div>
                                    </div>
                                {/each}
                            {/if}
                        </div>
                    </Collapse>
                {/each}
            {/if}
        </CardBody>
    </Card>

    <!-- ── Modal ─────────────────────────────────────────────────── -->
    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
        <ModalHeader toggle={() => (modalOpen = false)}>
            <i class={`bi ${editId ? 'bi-pencil-square' : 'bi-calendar-plus'} me-2`}></i>
            {editId ? 'Edit Periode' : 'Tambah Periode'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="p-nama">Nama</label>
                    <input id="p-nama" class="form-control" bind:value={form.nama} placeholder="mis. UAS Ganjil 2026" />
                    {#if form.errors.nama}<div class="text-danger small">{form.errors.nama}</div>{/if}
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-kategori">Kategori</label>
                    <select id="p-kategori" class="form-select" bind:value={form.kategori}>
                        {#each KATEGORI_LIST as k (k)}<option value={k}>{KATEGORI_INFO[k].label}</option>{/each}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-ta">Tahun Ajaran</label>
                    <select id="p-ta" class="form-select" bind:value={form.tahun_ajaran_id}>
                        {#each tahunAjaran as t (t.id)}<option value={t.id}>{t.name}{t.active ? ' (aktif)' : ''}</option>{/each}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-mulai">Mulai</label>
                    <input id="p-mulai" type="datetime-local" class="form-control" bind:value={form.tanggal_mulai} />
                    {#if form.errors.tanggal_mulai}<div class="text-danger small">{form.errors.tanggal_mulai}</div>{/if}
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-selesai">Selesai</label>
                    <input id="p-selesai" type="datetime-local" class="form-control" bind:value={form.tanggal_selesai} />
                    {#if form.errors.tanggal_selesai}<div class="text-danger small">{form.errors.tanggal_selesai}</div>{/if}
                </div>
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
    .accent-primary { background: var(--bs-primary); }
    .accent-warning { background: var(--bs-warning); }
    .accent-danger  { background: var(--bs-danger); }
    .accent-info    { background: var(--bs-info); }
    .accent-secondary { background: var(--bs-secondary); }
</style>
