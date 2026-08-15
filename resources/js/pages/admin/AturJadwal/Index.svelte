<script lang="ts">
    import { Badge, Button, Modal, ModalHeader, ModalBody, ModalFooter, FormGroup, Label, Input, Alert } from '@sveltestrap/sveltestrap';
    import { useForm, router } from '@inertiajs/svelte';
    import Select from '@/components/Select.svelte';
    import AturJadwalPengajarController from '@/actions/App/Http/Controllers/AturJadwalPengajarController';

    type EventJadwal = {
        id: number;
        hari: string;
        matpel: string;
        matpel_id: number;
        kelas: string;
        kelas_id: number;
        jam_mulai: string;
        jam_selesai: string;
        ruangan: string;
        color: string;
    };

    type GuruProps = {
        id: number;
        nama: string;
        nip: string;
        jabatan: string;
        walikelas: string[];
        foto: string | null;
    };

    type SelectOption = { value: number | string; label: string };

    let { guru = null, jadwal = null, matpelOptions = {}, kelasOptions = {} } = $props<{
        guru?: GuruProps | null;
        jadwal?: EventJadwal[] | null;
        matpelOptions?: Record<string, string>;
        kelasOptions?: Record<string, string>;
    }>();

    const hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const hariOrder = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    const guruData = $derived(guru);
    const jadwalList = $derived(Array.isArray(jadwal) ? jadwal : []);
    const hasJadwal = $derived(jadwalList.length > 0);

    const sortedJadwal = $derived.by(() => {
        if (!hasJadwal) {
            return [];
        }
        const sorted = [...jadwalList].sort((a, b) => {
            const ai = hariOrder.indexOf(a.hari);
            const bi = hariOrder.indexOf(b.hari);
            if (ai !== bi) return ai - bi;
            return a.jam_mulai.localeCompare(b.jam_mulai);
        });
        const seen = new Set<string>();
        return sorted.map((j) => {
            const first = !seen.has(j.hari);
            seen.add(j.hari);
            return { ...j, isFirst: first };
        });
    });

    const legend = $derived(
        [...new Map(jadwalList.map((e) => [e.matpel, e])).values()].map((e) => ({
            matpel: e.matpel,
            color: e.color,
        })),
    );

    const inisial = $derived(
        guruData && guruData.nama
            ? guruData.nama
                .split(',')[0]
                .split(' ')
                .slice(0, 2)
                .map((w: string) => w[0])
                .join('')
                .toUpperCase()
            : '',
    );

    const matpelSelectOptions = $derived<SelectOption[]>(
        Object.entries(matpelOptions ?? {}).map(([id, name]) => ({ value: Number(id), label: String(name) })),
    );

    const kelasSelectOptions = $derived<SelectOption[]>(
        Object.entries(kelasOptions ?? {}).map(([id, name]) => ({ value: Number(id), label: String(name) })),
    );

    let modalOpen = $state(false);
    let editingId = $state<number | null>(null);

    const form = useForm({
        matpel_id: null as number | null,
        kelas_id: null as number | null,
        hari: '',
        jam_mulai: '',
        jam_selesai: '',
    });

    function extractId(value: unknown): number | null
    {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        if (typeof value === 'object') {
            const obj = value as Record<string, unknown>;
            if (obj.value !== undefined && obj.value !== null && obj.value !== '') {
                return Number(obj.value);
            }
            return null;
        }
        const n = Number(value);
        return Number.isNaN(n) ? null : n;
    }

    function openCreate()
    {
        editingId = null;
        form.reset();
        form.matpel_id = null;
        form.kelas_id = null;
        form.hari = '';
        form.jam_mulai = '';
        form.jam_selesai = '';
        modalOpen = true;
    }

    function openEdit(item: EventJadwal)
    {
        editingId = item.id;
        form.reset();
        form.matpel_id = item.matpel_id;
        form.kelas_id = item.kelas_id;
        form.hari = item.hari;
        form.jam_mulai = item.jam_mulai;
        form.jam_selesai = item.jam_selesai;
        modalOpen = true;
    }

    function onMatpelChange(value: unknown)
    {
        form.matpel_id = extractId(value);
    }

    function onKelasChange(value: unknown)
    {
        form.kelas_id = extractId(value);
    }

    function onHariChange(value: string)
    {
        form.hari = value || '';
    }

    function submit()
    {
        if (editingId && guruData)
        {
            const route = AturJadwalPengajarController.update({ guru_id: guruData.id, jadwal: editingId });
            form.submit(
                { url: route.url, method: route.method },
                {
                    preserveScroll: true,
                    onSuccess: () => (modalOpen = false),
                },
            );
        }
        else if (guruData)
        {
            const route = AturJadwalPengajarController.store({ guru_id: guruData.id });
            form.submit(
                { url: route.url, method: route.method },
                {
                    preserveScroll: true,
                    onSuccess: () => (modalOpen = false),
                },
            );
        }
    }

    function confirmDelete(item: EventJadwal)
    {
        if (!confirm(`Hapus jadwal ${item.matpel} / ${item.kelas} (${item.hari})?`)) {
            return;
        }
        if (guruData) {
            router.delete(AturJadwalPengajarController.destroy({ guru_id: guruData.id, jadwal: item.id }).url);
        }
    }
</script>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-2 bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px">
                <i class="bi bi-calendar2-week fs-5"></i>
            </span>
            <div>
                <h5 class="fw-semibold mb-0 text-body">Jadwal Mengajar</h5>
                <p class="text-secondary mb-0 small">Penjadwalan kegiatan belajar mengajar mingguan</p>
            </div>
        </div>
        <Button color="primary" size="sm" onclick={openCreate}>
            <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
        </Button>
    </div>

    {#if guruData}
        <div class="card border rounded-3 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div
                    class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex align-items-center justify-content-center fw-semibold"
                    style="width:56px;height:56px;font-size:1.1rem"
                >
                    {inisial}
                </div>
                <div class="me-auto">
                    <div class="fw-semibold text-body">{guruData.nama}</div>
                    <div class="text-secondary small">NIP {guruData.nip} &middot; {guruData.jabatan}</div>
                    {#if guruData.walikelas.length}
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            {#each guruData.walikelas as w (w)}
                                <Badge color="info" class="fw-normal">Wali Kelas {w}</Badge>
                            {/each}
                        </div>
                    {/if}
                </div>
                {#if legend.length}
                    <div class="d-flex flex-wrap gap-2">
                        {#each legend as l (l.matpel)}
                            <span class="d-inline-flex align-items-center small text-secondary">
                                <span class="rounded-circle me-1" style="width:10px;height:10px;background:var(--bs-{l.color})"></span>
                                {l.matpel}
                            </span>
                        {/each}
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    <div class="card border rounded-3 overflow-hidden shadow-sm">
        <div class="card-body p-0">
            {#if hasJadwal}
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="min-width: 500px;">
                        <tbody style="font-size: 12px;">
                            {#each sortedJadwal as item (item.id)}
                                <tr>
                                    <td class="text-right border-right border-primary" style="width: 80px; border-top-width: medium; border-top-style: none; border-top-color: currentcolor;">
                                        {#if item.isFirst}
                                            <b>{item.hari}</b>
                                        {/if}
                                    </td>
                                    <td style="border-top-width: medium; border-top-style: none; border-top-color: currentcolor;">{item.matpel} / {item.kelas}  / {item.jam_mulai} - {item.jam_selesai}</td>
                                    <td class="text-end d-flex align-items-center w-100 justify-content-end" style="width: 60px; border-top-width: medium; border-top-style: none; border-top-color: currentcolor;">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary me-1"
                                            title="Edit"
                                            onclick={() => openEdit(item)}
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus"
                                            onclick={() => confirmDelete(item)}
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {:else}
                <div class="p-4">
                    <Alert color="info" class="d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-info-circle"></i>
                        <span>Tidak ada jadwal. Klik "Tambah Jadwal" untuk menambahkan.</span>
                    </Alert>
                </div>
            {/if}
        </div>
    </div>
</div>

<Modal isOpen={modalOpen} toggle={() => (modalOpen = false)} centered scrollable>
    <ModalHeader toggle={() => (modalOpen = false)}>
        {editingId ? 'Edit Jadwal' : 'Tambah Jadwal'}
    </ModalHeader>
    <ModalBody>
        <FormGroup>
            <Label for="hari">Hari</Label>
            <select
                id="hari"
                class="form-select"
                value={form.hari ?? ''}
                onchange={(e) => onHariChange((e.currentTarget as HTMLSelectElement).value)}
                disabled={form.processing}
            >
                <option value="">Pilih hari…</option>
                {#each hariList as h (h)}
                    <option value={h}>{h}</option>
                {/each}
            </select>
            {#if form.errors.hari}
                <small class="text-danger d-block mt-1">{form.errors.hari}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="matpel_id">Mata Pelajaran</Label>
            <Select
                items={matpelSelectOptions}
                value={form.matpel_id}
                placeholder="Pilih mata pelajaran…"
                getOptionValue={(item: SelectOption) => item.value}
                onchange={onMatpelChange}
                disabled={form.processing}
            />
            {#if form.errors.matpel_id}
                <small class="text-danger d-block mt-1">{form.errors.matpel_id}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="kelas_id">Kelas</Label>
            <Select
                items={kelasSelectOptions}
                value={form.kelas_id}
                placeholder="Pilih kelas…"
                getOptionValue={(item: SelectOption) => item.value}
                onchange={onKelasChange}
                disabled={form.processing}
            />
            {#if form.errors.kelas_id}
                <small class="text-danger d-block mt-1">{form.errors.kelas_id}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="jam_mulai">Jam Mulai</Label>
            <Input
                id="jam_mulai"
                type="time"
                bind:value={form.jam_mulai}
                invalid={!!form.errors.jam_mulai}
            />
            {#if form.errors.jam_mulai}
                <small class="text-danger d-block mt-1">{form.errors.jam_mulai}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="jam_selesai">Jam Selesai</Label>
            <Input
                id="jam_selesai"
                type="time"
                bind:value={form.jam_selesai}
                invalid={!!form.errors.jam_selesai}
            />
            {#if form.errors.jam_selesai}
                <small class="text-danger d-block mt-1">{form.errors.jam_selesai}</small>
            {/if}
        </FormGroup>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" outline onclick={() => (modalOpen = false)} disabled={form.processing}>
            Batal
        </Button>
        <Button color="primary" onclick={submit} disabled={form.processing}>
            {editingId ? 'Simpan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>
