<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import {
        Modal,
        ModalHeader,
        ModalBody,
        ModalFooter,
        FormGroup,
        Label,
        Input,
        Button,
    } from '@sveltestrap/sveltestrap';
    import Select from '@/components/Select.svelte';
    import KelasController from '@/actions/App/Http/Controllers/Admin/KelasController';

    type SelectOption = { value: number | string; label: string };

    let {
        kelas_parent = [],
        kelas_list = [],
        jurusans = [],
        gurus = [],
    }: {
        kelas_parent?: Record<string, any>[];
        kelas_list?: Record<string, any>[];
        jurusans?: Record<string, any>[];
        gurus?: Record<string, any>[];
    } = $props();

    let items = $derived(kelas_parent ?? []);

    const jurusanOptions = $derived<SelectOption[]>(
        jurusans.map((j) => ({ value: j.id, label: j.name })),
    );
    const guruOptions = $derived<SelectOption[]>(
        gurus.map((g) => ({ value: g.id, label: g.nama_lengkap })),
    );

    let editingId = $state<number | null>(null);
    let modalOpen = $state(false);

    const parentGroups = $derived.by(() => {
        const list = kelas_list ?? [];
        const childrenOf = new Map<number | null, Record<string, any>[]>();
        for (const k of list) {
            const pid = (k.parent_id ?? null) as number | null;
            if (!childrenOf.has(pid)) {
                childrenOf.set(pid, []);
            }
            childrenOf.get(pid)!.push(k);
        }

        const excluded = new Set<number>();
        if (editingId) {
            const stack = [editingId];
            while (stack.length) {
                const cur = stack.pop()!;
                excluded.add(cur);
                for (const child of childrenOf.get(cur) ?? []) {
                    stack.push(child.id);
                }
            }
        }

        const roots = (childrenOf.get(null) ?? [])
            .slice()
            .sort((a, b) => String(a.nama).localeCompare(String(b.nama)));

        const groups: { label: string; options: SelectOption[] }[] = [];
        for (const root of roots) {
            const options: SelectOption[] = [];
            const walk = (node: Record<string, any>, depth: number) => {
                if (excluded.has(node.id)) {
                    return;
                }
                options.push({
                    value: node.id,
                    label: depth > 0 ? `${'  '.repeat(depth)}${node.nama}` : node.nama,
                });
                for (const child of childrenOf.get(node.id) ?? []) {
                    walk(child, depth + 1);
                }
            };
            walk(root, 0);
            if (options.length) {
                groups.push({ label: root.nama, options });
            }
        }

        return groups;
    });

    let namaTouched = $state(false);

    const form = useForm({
        nama: '',
        deskripsi: '',
        ruangan: '',
        jurusan_id: null as number | null,
        guru_id: null as number | null,
        parent_id: null as number | null,
        active: false,
    });

    function extractId(value: unknown): number | null {
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

    function rebuildNama() {
        if (namaTouched) {
            return;
        }
        const parent = (kelas_list ?? []).find((k) => k.id === form.parent_id);
        const jurusan = (jurusans ?? []).find((j) => j.id === form.jurusan_id);
        const parts: string[] = [];
        if (parent) {
            parts.push(parent.nama);
        }
        if (jurusan) {
            parts.push(jurusan.kode || jurusan.name);
        }
        if (parts.length === 0) {
            form.nama = form.ruangan ? `- ${form.ruangan}` : '';
            return;
        }
        let base = parts.join(' ');
        if (form.ruangan) {
            base += ` - ${form.ruangan}`;
        }
        form.nama = base;
    }

    function openCreate() {
        editingId = null;
        namaTouched = false;
        form.reset();
        form.active = false;
        form.jurusan_id = null;
        form.guru_id = null;
        form.parent_id = null;
        form.ruangan = '';
        modalOpen = true;
    }

    function openEdit(node: Record<string, any>) {
        editingId = node.id;
        namaTouched = false;
        form.reset();
        form.nama = node.nama ?? '';
        form.deskripsi = node.deskripsi ?? '';
        form.ruangan = node.ruangan ?? '';
        form.jurusan_id = node.jurusan?.id ?? null;
        form.guru_id = node.walikelas?.id ?? null;
        form.parent_id = node.parent_id ?? null;
        form.active = !!node.active;
        modalOpen = true;
    }

    function onParentChange(value: string) {
        form.parent_id = value === '' ? null : Number(value);
        rebuildNama();
    }

    function onJurusanChange(value: unknown) {
        form.jurusan_id = extractId(value);
        rebuildNama();
    }

    function onGuruChange(value: unknown) {
        form.guru_id = extractId(value);
    }

    function onRuanganChange() {
        rebuildNama();
    }

    function onNamaInput() {
        namaTouched = true;
    }

    function submit() {
        if (editingId) {
            const route = KelasController.update({ kelas: editingId });
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: () => (modalOpen = false) },
            );
        } else {
            const route = KelasController.store();
            form.submit(
                { url: route.url, method: route.method },
                { onSuccess: () => (modalOpen = false) },
            );
        }
    }

    function confirmDelete(node: Record<string, any>) {
        if (!confirm(`Hapus kelas "${node.nama}"?`)) {
            return;
        }
        router.delete(KelasController.destroy({ kelas: node.id }).url);
    }
</script>

{#snippet node(item, depth)}
    <li class="kelas-tree__node">
        <div class="kelas-tree__label">
            {#if item.children && item.children.length}
                <i class="bi bi-folder2-open text-warning"></i>
            {:else}
                <i class="bi bi-mortarboard-fill text-primary"></i>
            {/if}
            <span class="fw-semibold">{item.nama}</span>
            {#if item.jurusan}
                <span class="badge text-bg-info ms-2">{item.jurusan.name}</span>
            {/if}
            {#if item.walikelas}
                <span class="text-muted ms-2"
                    >Wali: {item.walikelas.nama_lengkap}</span
                >
            {/if}
            <span class="kelas-tree__actions">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    onclick={() => openEdit(item)}
                >
                    <i class="bi bi-pencil"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick={() => confirmDelete(item)}
                >
                    <i class="bi bi-trash"></i>
                </button>
            </span>
        </div>
        {#if item.children && item.children.length}
            <ul class="kelas-tree__children">
                {#each item.children as child (child.id)}
                    {@render node(child, depth + 1)}
                {/each}
            </ul>
        {/if}
    </li>
{/snippet}

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <h1 class="h4 fw-semibold mb-0">Kelas</h1>
        <Button color="primary" onclick={openCreate}>
            <i class="bi bi-plus-lg me-1"></i> Tambah Kelas
        </Button>
    </div>
    <p class="text-muted mb-3">
        Struktur kelas ditampilkan sebagai hierarki orang tua–anak berdasarkan
        <code>parent_id</code>. Nama kelas dibentuk otomatis dari induk + jurusan
        + ruangan (mis. <code>X RPL - Ruang 1</code>).
    </p>

    {#if items.length}
        <ul class="kelas-tree">
            {#each items as parent (parent.id)}
                {@render node(parent, 0)}
            {/each}
        </ul>
    {:else}
        <p class="text-muted">Belum ada data kelas.</p>
    {/if}
</div>

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editingId ? 'Edit Kelas' : 'Tambah Kelas'}
    </ModalHeader>
    <ModalBody>
        <FormGroup>
            <Label for="parent_id">Kelas Induk</Label>
            <select
                id="parent_id"
                class="form-select"
                value={form.parent_id ?? ''}
                onchange={(e) =>
                    onParentChange((e.currentTarget as HTMLSelectElement).value)}
            >
                <option value="">Tidak ada (kelas induk)</option>
                {#each parentGroups as group (group.label)}
                    <optgroup label={group.label}>
                        {#each group.options as opt (opt.value)}
                            <option value={opt.value}>{opt.label}</option>
                        {/each}
                    </optgroup>
                {/each}
            </select>
            <small class="text-muted"
                >Jika dipilih, nama kelas akan otomatis diawali nama induk.</small
            >
        </FormGroup>

        <FormGroup>
            <Label for="nama">Nama Kelas</Label>
            <Input
                id="nama"
                bind:value={form.nama}
                oninput={onNamaInput}
                invalid={!!form.errors.nama}
            />
            <small class="text-muted"
                >Otomatis: induk + jurusan + ruangan. Ubah manual untuk
                menimpa.</small
            >
            {#if form.errors.nama}
                <small class="text-danger">{form.errors.nama}</small>
            {/if}
        </FormGroup>

        <FormGroup>
            <Label for="jurusan_id">Jurusan</Label>
            <Select
                items={jurusanOptions}
                value={form.jurusan_id}
                placeholder="Pilih jurusan…"
                getOptionValue={(item) => item.value}
                onchange={onJurusanChange}
            />
        </FormGroup>

        <FormGroup>
            <Label for="ruangan">Ruangan</Label>
            <Input
                id="ruangan"
                bind:value={form.ruangan}
                oninput={onRuanganChange}
                placeholder="Mis. Ruang 1"
            />
        </FormGroup>

        <FormGroup>
            <Label for="guru_id">Wali Kelas</Label>
            <Select
                items={guruOptions}
                value={form.guru_id}
                placeholder="Pilih wali kelas…"
                getOptionValue={(item) => item.value}
                onchange={onGuruChange}
            />
        </FormGroup>

        <FormGroup>
            <Label for="deskripsi">Deskripsi</Label>
            <Input
                id="deskripsi"
                type="textarea"
                bind:value={form.deskripsi}
            />
        </FormGroup>

        <div class="crud-checkbox">
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
            <Label for="active" class="crud-checkbox__label">Aktif</Label>
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" outline onclick={() => (modalOpen = false)}>
            Batal
        </Button>
        <Button color="primary" onclick={submit} disabled={form.processing}>
            {editingId ? 'Simpan Perubahan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>

<style>
    .kelas-tree,
    .kelas-tree__children {
        list-style: none;
        padding-left: 1.25rem;
        margin: 0;
    }

    .kelas-tree {
        padding-left: 0;
    }

    .kelas-tree__children {
        border-left: 2px solid #e9ecef;
        margin-left: 0.5rem;
    }

    .kelas-tree__node {
        padding: 0.35rem 0;
    }

    .kelas-tree__label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .kelas-tree__actions {
        margin-left: auto;
        display: flex;
        gap: 0.25rem;
    }

    .crud-checkbox {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .crud-checkbox__label {
        margin: 0;
        cursor: pointer;
    }

    .crud-toggle__track {
        width: 42px;
        height: 24px;
        border-radius: 999px;
        background: #ced4da;
        border: none;
        position: relative;
        cursor: pointer;
        transition: background 0.15s ease;
        padding: 0;
    }

    .crud-toggle__track.is-on {
        background: #198754;
    }

    .crud-toggle__knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.15s ease;
    }

    .crud-toggle__track.is-on .crud-toggle__knob {
        transform: translateX(18px);
    }
</style>
