<script lang="ts">
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Modal,
        ModalHeader,
        ModalBody,
        ModalFooter,
        FormGroup,
        Label,
    } from '@sveltestrap/sveltestrap';
    import { router, useForm } from '@inertiajs/svelte';
    import SiswaController from '@/actions/App/Http/Controllers/Admin/SiswaController';
    import SiswaKelasController from '@/actions/App/Http/Controllers/Admin/SiswaKelasController';
    import Select from '@/components/Select.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';

    interface KelasItem {
        id: number;
        kelas_id: number;
        nama_kelas: string | null;
        tahun_ajaran_id: number | null;
        tahun_ajaran: string | null;
        active: boolean;
        pertama_masuk?: boolean;
    }

    type SelectOption = { value: number | string; label: string };

    interface Props {
        siswa_nisn: string;
        nama: string;
        nis: string | null;
        foto_profil?: string | null;
        kelas_saya: KelasItem[];
        daftar_kelas: { id: number; nama: string }[];
        tahun_ajaran: { id: number; name: string; active: boolean }[];
        tahun_ajaran_aktif: number | null;
    }

    let {
        siswa_nisn,
        nama = '',
        nis = null,
        foto_profil = null,
        kelas_saya = [],
        daftar_kelas = [],
        tahun_ajaran = [],
        tahun_ajaran_aktif = null,
    }: Props = $props();

    const kelasOptions = $derived<SelectOption[]>(
        daftar_kelas.map((k) => ({ value: k.id, label: k.nama })),
    );
    const tahunAjaranOptions = $derived<SelectOption[]>(
        tahun_ajaran.map((t) => ({ value: t.id, label: t.name })),
    );

    const hasClasses = $derived(kelas_saya.length > 0);

    function extractId(value: unknown): number | null {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        if (typeof value === 'object') {
            const obj = value as Record<string, unknown>;
            if (
                obj.value !== undefined &&
                obj.value !== null &&
                obj.value !== ''
            ) {
                return Number(obj.value);
            }
            return null;
        }
        const n = Number(value);
        return Number.isNaN(n) ? null : n;
    }

    let modalOpen = $state(false);
    let editingId = $state<number | null>(null);

    const form = useForm({
        kelas_id: null as number | null,
        tahun_ajaran_id: tahun_ajaran_aktif,
        active: true,
        pertama_masuk: false,
    });

    function openCreate() {
        editingId = null;
        form.reset();
        form.active = true;
        form.pertama_masuk = false;
        form.kelas_id = null;
        form.tahun_ajaran_id = tahun_ajaran_aktif;
        modalOpen = true;
    }

    function openEdit(item: KelasItem) {
        editingId = item.id;
        form.reset();
        form.kelas_id = item.kelas_id;
        form.tahun_ajaran_id = item.tahun_ajaran_id;
        form.active = item.active;
        form.pertama_masuk = item.pertama_masuk ?? false;
        modalOpen = true;
    }

    function closeModal() {
        modalOpen = false;
    }

    function submit() {
        if (editingId) {
            const route = SiswaKelasController.update({
                siswa: siswa_nisn,
                siswaKelas: editingId,
            });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: closeModal },
            );
        } else {
            const route = SiswaKelasController.store({
                siswa: siswa_nisn,
            });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: closeModal },
            );
        }
    }

    function confirmDelete(item: KelasItem) {
        const label = item.nama_kelas ?? 'kelas';
        if (
            !confirm(
                `Hapus penempatan "${label}" untuk siswa ini?\nPenempatan ini akan dihapus secara permanen.`,
            )
        ) {
            return;
        }
        router.delete(
            SiswaKelasController.destroy({
                siswa: siswa_nisn,
                siswaKelas: item.id,
            }).url,
        );
    }

    function goBack() {
        router.visit(SiswaController.index().url);
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Atur Kelas Siswa"
        subtitle={`Kelola penempatan kelas untuk ${nama || '-'}`}
    >
        {#snippet actions()}
            <Button
                color="secondary"
                outline
                size="sm"
                onclick={goBack}
            >
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Peserta Didik
            </Button>
            <Button color="primary" size="sm" onclick={openCreate}>
                <i class="bi bi-plus-lg me-1"></i> Tambah Kelas
            </Button>
        {/snippet}
    </PageHeader>

    <div class="card border rounded-1 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <Avatar
                    src={foto_profil}
                    name={nama}
                    size={56}
                />
                <div>
                    <div class="fw-semibold text-body fs-5">
                        {nama}
                    </div>
                    <div class="text-secondary small">
                        NISN {siswa_nisn}
                        {#if nis}· NIS {nis}{/if}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Card class="border rounded-1 shadow-sm">
        <CardBody class="py-5">
            {#if hasClasses}
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    {#each kelas_saya as item (item.id)}
                        <div
                            class="d-inline-flex align-items-center gap-2 bg-white border rounded px-3 py-2 shadow-sm"
                        >
                            <i
                                class="bi bi-collection text-primary"
                            ></i>
                            <span class="text-sm fw-semibold"
                                >{item.nama_kelas}</span
                            >
                            <span class="text-secondary small">
                                {item.tahun_ajaran ?? 'Tanpa tahun ajaran'}
                            </span>
                            {#if item.active}
                                <Badge color="success" class="ms-1"
                                    >Aktif</Badge
                                >
                            {:else}
                                <Badge color="secondary" class="ms-1"
                                    >Nonaktif</Badge
                                >
                            {/if}
                            {#if item.pertama_masuk}
                                <Badge color="warning" class="ms-1"
                                    >Pertama Masuk</Badge
                                >
                            {/if}
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary py-0 px-1"
                                onclick={() => openEdit(item)}
                                title="Edit"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger py-0 px-1"
                                onclick={() => confirmDelete(item)}
                                title="Hapus"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="text-center text-secondary">
                    <i
                        class="bi bi-emoji-frown"
                        style="font-size: 3rem"
                    ></i>
                    <p class="mt-3 mb-0">
                        Belum ada kelas untuk peserta didik ini.
                    </p>
                    <Button
                        color="primary"
                        size="sm"
                        class="mt-3"
                        onclick={openCreate}
                    >
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kelas
                    </Button>
                </div>
            {/if}
        </CardBody>
    </Card>
</div>

<Modal
    isOpen={modalOpen}
    toggle={() => (modalOpen = !modalOpen)}
    backdrop="static"
>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editingId ? 'Edit Kelas' : 'Tambah Kelas Baru'}
    </ModalHeader>
    <ModalBody>
        <FormGroup>
            <Label for="kelas_id">Kelas</Label>
            <Select
                items={kelasOptions}
                value={form.kelas_id}
                placeholder="Pilih kelas…"
                getOptionValue={(item) => item.value}
                onchange={(value) => (form.kelas_id = extractId(value))}
            />
            {#if form.errors.kelas_id}
                <small class="text-danger d-block mt-1"
                    >{form.errors.kelas_id}</small
                >
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="tahun_ajaran_id">Tahun Ajaran</Label>
            <select
                id="tahun_ajaran_id"
                class="form-select"
                value={form.tahun_ajaran_id ?? ''}
                onchange={(e) =>
                    (form.tahun_ajaran_id =
                        (e.currentTarget as HTMLSelectElement).value === ''
                            ? null
                            : Number(
                                  (
                                      e.currentTarget as HTMLSelectElement
                                  ).value,
                              ))}
            >
                <option value="">Pilih tahun ajaran…</option>
                {#each tahun_ajaran as tahun (tahun.id)}
                    <option value={tahun.id}>
                        {tahun.name}
                        {#if tahun.active}
                            (Aktif)
                        {/if}
                    </option>
                {/each}
            </select>
            {#if form.errors.tahun_ajaran_id}
                <small class="text-danger d-block mt-1"
                    >{form.errors.tahun_ajaran_id}</small
                >
            {/if}
        </FormGroup>

        <div class="crud-checkbox mb-2">
            <!-- svelte-ignore a11y_consider_explicit_label -->
            <button
                type="button"
                class="crud-toggle__track"
                class:is-on={form.active}
                role="switch"
                aria-checked={form.active ? 'true' : 'false'}
                onclick={() => (form.active = !form.active)}
            >
                <span class="crud-toggle__knob"></span>
            </button>
            <Label for="active" class="crud-checkbox__label"
                >Aktif</Label
            >
        </div>

        <div class="crud-checkbox">
            <!-- svelte-ignore a11y_consider_explicit_label -->
            <button
                type="button"
                class="crud-toggle__track"
                class:is-on={form.pertama_masuk}
                role="switch"
                aria-checked={form.pertama_masuk ? 'true' : 'false'}
                onclick={() => (form.pertama_masuk = !form.pertama_masuk)}
            >
                <span class="crud-toggle__knob"></span>
            </button>
            <Label for="pertama_masuk" class="crud-checkbox__label"
                >Pertama Masuk</Label
            >
        </div>
    </ModalBody>
    <ModalFooter>
        <Button
            color="secondary"
            outline
            onclick={() => (modalOpen = !modalOpen)}>Batal</Button
        >
        <Button
            color="primary"
            onclick={submit}
            disabled={form.processing}
        >
            {editingId ? 'Simpan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>

<style>
    .crud-checkbox {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .crud-checkbox__label {
        margin-bottom: 0;
        font-weight: 500;
        cursor: pointer;
    }

    .crud-toggle__track {
        position: relative;
        width: 46px;
        height: 26px;
        border-radius: 999px;
        border: none;
        background: var(--bs-secondary);
        cursor: pointer;
        padding: 0;
        transition: background 0.2s ease;
    }

    .crud-toggle__track.is-on {
        background: var(--bs-success);
    }

    .crud-toggle__knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--inv-white);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s ease;
    }

    .crud-toggle__track.is-on .crud-toggle__knob {
        transform: translateX(20px);
    }
</style>
