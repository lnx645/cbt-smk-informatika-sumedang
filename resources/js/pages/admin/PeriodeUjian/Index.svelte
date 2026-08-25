<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
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

    const kategoriOptions: KategoriUjian[] = ['uts', 'uas', 'usbk'];

    const form = useForm({
        kategori: 'uts' as KategoriUjian,
        tahun_ajaran_id: tahunAjaran.find((t) => t.active)?.id ?? tahunAjaran[0]?.id ?? null,
        nama: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    let modalOpen = $state(false);
    let editId = $state<number | null>(null);

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
        const onSuccess = () => {
            modalOpen = false;
            form.reset();
        };
        if (editId) {
            form.put(PeriodeUjianController.update({ periodeUjian: editId }).url, { onSuccess });
        } else {
            form.post(PeriodeUjianController.store().url, { onSuccess });
        }
    }

    async function hapus(p: PeriodeItem) {
        const ok = await confirm.show({
            title: 'Hapus Periode',
            message: `Periode "${p.nama}" akan dihapus. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(PeriodeUjianController.destroy({ periodeUjian: p.id }).url);
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Periode Ujian"
        subtitle="Tetapkan jendela jadwal UTS/UAS/USBK. Guru hanya bisa menjadwalkan ujian besar di dalam periode ini."
    >
        {#snippet actions()}
            <Button color="primary" onclick={bukaBuat}>
                <i class="bi bi-plus-lg me-1"></i>Tambah Periode
            </Button>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            {#if periode.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x display-5 d-block mb-2"></i>
                    <div>Belum ada periode ujian.</div>
                </div>
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Tahun Ajaran</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each periode as p (p.id)}
                                <tr>
                                    <td class="fw-semibold">{p.nama}</td>
                                    <td>
                                        <Badge color={KATEGORI_INFO[p.kategori].color} pill>
                                            {KATEGORI_INFO[p.kategori].label}
                                        </Badge>
                                    </td>
                                    <td>{p.tahun_ajaran ?? '—'}</td>
                                    <td class="small">{p.tanggal_mulai}</td>
                                    <td class="small">{p.tanggal_selesai}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(p)}>
                                                <i class="bi bi-pencil"></i>
                                            </Button>
                                            <Button size="sm" color="outline-danger" onclick={() => hapus(p)}>
                                                <i class="bi bi-trash"></i>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </CardBody>
    </Card>

    <Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
        <ModalHeader toggle={() => (modalOpen = false)}>
            {editId ? 'Edit Periode' : 'Tambah Periode'}
        </ModalHeader>
        <ModalBody>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="p-nama">Nama Periode</label>
                    <input id="p-nama" class="form-control" bind:value={form.nama} placeholder="mis. UAS Ganjil 2026" />
                    {#if form.errors.nama}<div class="text-danger small">{form.errors.nama}</div>{/if}
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-kategori">Kategori</label>
                    <select id="p-kategori" class="form-select" bind:value={form.kategori}>
                        {#each kategoriOptions as k (k)}
                            <option value={k}>{KATEGORI_INFO[k].label}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-ta">Tahun Ajaran</label>
                    <select id="p-ta" class="form-select" bind:value={form.tahun_ajaran_id}>
                        {#each tahunAjaran as t (t.id)}
                            <option value={t.id}>{t.name}{t.active ? ' (aktif)' : ''}</option>
                        {/each}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-mulai">Tanggal Mulai</label>
                    <input id="p-mulai" type="datetime-local" class="form-control" bind:value={form.tanggal_mulai} />
                    {#if form.errors.tanggal_mulai}<div class="text-danger small">{form.errors.tanggal_mulai}</div>{/if}
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="p-selesai">Tanggal Selesai</label>
                    <input id="p-selesai" type="datetime-local" class="form-control" bind:value={form.tanggal_selesai} />
                    {#if form.errors.tanggal_selesai}<div class="text-danger small">{form.errors.tanggal_selesai}</div>{/if}
                </div>
            </div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
            <Button color="primary" onclick={simpan} disabled={form.processing}>Simpan</Button>
        </ModalFooter>
    </Modal>
</div>
