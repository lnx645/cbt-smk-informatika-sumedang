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
        Input,
        TabContent,
        TabPane,
    } from '@sveltestrap/sveltestrap';
    import { router, useForm } from '@inertiajs/svelte';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import GuruKelasController from '@/actions/App/Http/Controllers/Admin/GuruKelasController';
    import Select from '@/components/Select.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';

    interface MatpelItem {
        id: number;
        nama: string | null;
        matpel_id: number | null;
        kelas_id: number;
        tahun_ajaran_id: number | null;
        aktif: boolean;
        active_forum: boolean;
        lihat_anggota_kelas: boolean;
        kode_undangan: string | null;
    }

    interface KelasItem {
        nama_kelas: string;
        matpels: MatpelItem[];
    }

    type SelectOption = { value: number | string; label: string };

    interface Props {
        guru_id: number;
        nama: string;
        nip: string | null;
        foto_profil?: string | null;
        kelas: KelasItem[];
        daftar_kelas: { id: number; nama: string }[];
        matpels: { id: number; name: string }[];
        tahun_ajaran: { id: number; name: string; active: boolean }[];
        tahun_ajaran_aktif: number | null;
    }

    let {
        guru_id,
        nama = '',
        nip = null,
        foto_profil = null,
        kelas = [],
        daftar_kelas = [],
        matpels = [],
        tahun_ajaran = [],
        tahun_ajaran_aktif = null,
    }: Props = $props();

    const kelasOptions = $derived<SelectOption[]>(
        daftar_kelas.map((k) => ({ value: k.id, label: k.nama })),
    );
    const matpelOptions = $derived<SelectOption[]>(
        matpels.map((m) => ({ value: m.id, label: m.name })),
    );
    const tahunAjaranOptions = $derived<SelectOption[]>(
        tahun_ajaran.map((t) => ({ value: t.id, label: t.name })),
    );

    const hasClasses = $derived(kelas.length > 0);

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

    const validMatpels = (item: KelasItem) =>
        item.matpels.filter(
            (m) => m.nama !== null && m.nama !== undefined,
        );

    let modalOpen = $state(false);
    let editingId = $state<number | null>(null);

    const form = useForm({
        kelas_id: null as number | null,
        matpel_id: null as number | null,
        tahun_ajaran_id: tahun_ajaran_aktif,
        aktif: true,
        active_forum: false,
        lihat_anggota_kelas: false,
    });

    function openCreate() {
        editingId = null;
        form.reset();
        form.aktif = true;
        form.matpel_id = null;
        form.kelas_id = null;
        form.tahun_ajaran_id = tahun_ajaran_aktif;
        form.active_forum = false;
        form.lihat_anggota_kelas = false;
        modalOpen = true;
    }

    function openEdit(item: MatpelItem) {
        editingId = item.id;
        form.reset();
        form.kelas_id = item.kelas_id;
        form.matpel_id = item.matpel_id;
        form.tahun_ajaran_id = item.tahun_ajaran_id;
        form.aktif = item.aktif;
        form.active_forum = item.active_forum;
        form.lihat_anggota_kelas = item.lihat_anggota_kelas;
        modalOpen = true;
    }

    function closeModal() {
        modalOpen = false;
    }

    function submit() {
        if (editingId) {
            const route = GuruKelasController.update({
                guru: guru_id,
                guruKelas: editingId,
            });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: closeModal },
            );
        } else {
            const route = GuruKelasController.store({
                guru: guru_id,
            });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: closeModal },
            );
        }
    }

    function confirmDelete(item: MatpelItem) {
        const label = item.nama ?? 'mata pelajaran';
        if (
            !confirm(
                `Hapus penugasan "${label}" untuk guru ini?\nPenugasan ini akan dihapus secara permanen.`,
            )
        ) {
            return;
        }
        router.delete(
            GuruKelasController.destroy({
                guru: guru_id,
                guruKelas: item.id,
            }).url,
        );
    }

    function goBack() {
        router.visit(PengajarController.index().url);
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Atur Guru Kelas"
        subtitle={`Kelola plotting mata pelajaran untuk ${nama || '-'}`}
    >
        {#snippet actions()}
            <Button
                color="secondary"
                outline
                size="sm"
                onclick={goBack}
            >
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pengajar
            </Button>
            <Button color="primary" size="sm" onclick={openCreate}>
                <i class="bi bi-plus-lg me-1"></i> Tambah
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
                        NIP {nip ?? '-'}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {#if hasClasses}
        <TabContent>
            {#each kelas as item, key (key)}
                <TabPane
                    tab={item.nama_kelas}
                    tabId={key}
                    active={key === 0}
                >
                    <div class="mt-3">
                        {#if validMatpels(item).length}
                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                {#each validMatpels(item) as matpel (matpel.id)}
                                    <div
                                        class="d-inline-flex align-items-center gap-2 bg-white border rounded px-3 py-2 shadow-sm"
                                    >
                                        <i
                                            class="bi bi-book-half text-primary"
                                        ></i>
                                        <span
                                            class="text-sm fw-semibold"
                                            >{matpel?.nama}</span
                                        >
                                        {#if !matpel.aktif}
                                            <Badge
                                                color="secondary"
                                                class="ms-1"
                                                >Nonaktif</Badge
                                            >
                                        {:else}
                                            <Badge
                                                color="success"
                                                class="ms-1"
                                                >Aktif</Badge
                                            >
                                        {/if}
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-1"
                                            onclick={() =>
                                                openEdit(matpel)}
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"
                                            ></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger py-0 px-1"
                                            onclick={() =>
                                                confirmDelete(matpel)}
                                            title="Hapus"
                                        >
                                            <i class="bi bi-trash"
                                            ></i>
                                        </button>
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <div
                                class="d-flex flex-column align-items-center justify-content-center text-center py-4 border rounded bg-light"
                            >
                                <i
                                    class="bi bi-book text-secondary"
                                    style="font-size: 2rem"
                                ></i>
                                <p
                                    class="text-secondary small mb-0 mt-2"
                                >
                                    Belum ada mata pelajaran yang
                                    ditingkatkan.
                                </p>
                            </div>
                        {/if}
                    </div>
                </TabPane>
            {/each}
        </TabContent>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i
                        class="bi bi-emoji-frown"
                        style="font-size: 3rem"
                    ></i>
                    <p class="mt-3 mb-0">
                        Belum ada kelas yang diampu oleh guru ini.
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
            </CardBody>
        </Card>
    {/if}
</div>

<Modal
    isOpen={modalOpen}
    toggle={() => (modalOpen = !modalOpen)}
    backdrop="static"
>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editingId ? 'Edit Penugasan' : 'Tambah Penugasan Baru'}
    </ModalHeader>
    <ModalBody>
        <FormGroup>
            <Label for="kelas_id">Kelas</Label>
            <Select
                items={kelasOptions}
                value={form.kelas_id}
                placeholder="Pilih kelas…"
                getOptionValue={(item) => item.value}
                onchange={(value) =>
                    (form.kelas_id = extractId(value))}
            />
            {#if form.errors.kelas_id}
                <small class="text-danger d-block mt-1"
                    >{form.errors.kelas_id}</small
                >
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="matpel_id">Mata Pelajaran</Label>
            <Select
                items={matpelOptions}
                value={form.matpel_id}
                placeholder="Pilih mata pelajaran…"
                getOptionValue={(item) => item.value}
                onchange={(value) =>
                    (form.matpel_id = extractId(value))}
            />
            {#if form.errors.matpel_id}
                <small class="text-danger d-block mt-1"
                    >{form.errors.matpel_id}</small
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
                        (e.currentTarget as HTMLSelectElement)
                            .value === ''
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
                class:is-on={form.aktif}
                role="switch"
                aria-checked={form.aktif ? 'true' : 'false'}
                onclick={() => (form.aktif = !form.aktif)}
            >
                <span class="crud-toggle__knob"></span>
            </button>
            <Label for="aktif" class="crud-checkbox__label"
                >Aktif</Label
            >
        </div>

        <div class="crud-checkbox mb-2">
            <!-- svelte-ignore a11y_consider_explicit_label -->
            <button
                type="button"
                class="crud-toggle__track"
                class:is-on={form.active_forum}
                role="switch"
                aria-checked={form.active_forum ? 'true' : 'false'}
                onclick={() =>
                    (form.active_forum = !form.active_forum)}
            >
                <span class="crud-toggle__knob"></span>
            </button>
            <Label for="active_forum" class="crud-checkbox__label"
                >Forum Aktif</Label
            >
        </div>

        <div class="crud-checkbox">
            <!-- svelte-ignore a11y_consider_explicit_label -->
            <button
                type="button"
                class="crud-toggle__track"
                class:is-on={form.lihat_anggota_kelas}
                role="switch"
                aria-checked={form.lihat_anggota_kelas
                    ? 'true'
                    : 'false'}
                onclick={() =>
                    (form.lihat_anggota_kelas =
                        !form.lihat_anggota_kelas)}
            >
                <span class="crud-toggle__knob"></span>
            </button>
            <Label
                for="lihat_anggota_kelas"
                class="crud-checkbox__label"
                >Lihat Anggota Kelas</Label
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
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s ease;
    }

    .crud-toggle__track.is-on .crud-toggle__knob {
        transform: translateX(20px);
    }
</style>
