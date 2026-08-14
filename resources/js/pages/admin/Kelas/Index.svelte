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
            base += ` ${form.ruangan}`;
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
        const parent = (kelas_list ?? []).find((k) => k.id === form.parent_id);
        form.jurusan_id = parent?.jurusan_id ?? null;
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

    function countDescendants(node: Record<string, any>): number {
        if (!node.children || !node.children.length) {
            return 0;
        }
        let total = 0;
        for (const child of node.children) {
            total += 1 + countDescendants(child);
        }
        return total;
    }

    function confirmDelete(node: Record<string, any>) {
        const count = countDescendants(node);
        const message =
            count > 0
                ? `Hapus kelas "${node.nama}" beserta ${count} anak kelas terkait?`
                : `Hapus kelas "${node.nama}"?`;
        if (!confirm(message)) {
            return;
        }
        router.delete(KelasController.destroy({ kelas: node.id }).url);
    }
</script>

{#snippet node(item, depth)}
    <li class="kt-node">
        <div class="kt-row" style={`--depth:${depth}`}>
            <span
                class="kt-icon {item.children && item.children.length
                    ? 'kt-icon--parent'
                    : 'kt-icon--leaf'}"
            >
                {#if item.children && item.children.length}
                    <i class="bi bi-folder2"></i>
                {:else}
                    <i class="bi bi-mortarboard-fill"></i>
                {/if}
            </span>
            <div class="kt-body">
                <div class="kt-title">
                    <span class="kt-name">{item.nama}</span>
                    {#if item.active}
                        <span class="kt-badge">Aktif</span>
                    {/if}
                </div>
                <div class="kt-meta">
                    {#if item.jurusan}
                        <span class="kt-chip kt-chip--jurusan"
                            ><i class="bi bi-bookmark-fill"></i>
                            {item.jurusan.name}</span
                        >
                    {/if}
                    {#if item.ruangan}
                        <span class="kt-meta-item"
                            ><i class="bi bi-door-closed-fill"></i> Ruang {item.ruangan}</span
                        >
                    {/if}
                    {#if item.walikelas}
                        <span class="kt-meta-item"
                            ><i class="bi bi-person-fill"></i>
                            {item.walikelas.nama_lengkap}</span
                        >
                    {/if}
                    {#if !item.jurusan && !item.ruangan && !item.walikelas}
                        <span class="kt-meta-empty">Belum ada detail</span>
                    {/if}
                </div>
            </div>
            <span class="kt-actions">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    onclick={() => openEdit(item)}
                    title="Edit"
                >
                    <i class="bi bi-pencil"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick={() => confirmDelete(item)}
                    title="Hapus"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </span>
        </div>
        {#if item.children && item.children.length}
            <ul class="kt-children">
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
        + ruangan (mis. <code>XI RPL 5</code>).
    </p>

    {#if items.length}
        <div class="kt-card card">
            <ul class="kt-tree">
                {#each items as parent (parent.id)}
                    {@render node(parent, 0)}
                {/each}
            </ul>
        </div>
    {:else}
        <div class="text-center text-muted py-5 border rounded bg-light">
            <i class="bi bi-diagram-3 fs-1 d-block mb-2 opacity-50"></i>
            Belum ada data kelas.
        </div>
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
    .kt-card {
        background: #ffffff;
        overflow: hidden;
    }

    .kt-tree,
    .kt-children {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .kt-tree {
        padding: 0.6rem;
    }

    /* ── Indentation for hierarchy (no connector lines) ── */
    .kt-children {
        margin-left: 1.4rem;
        padding-left: 0;
    }

    .kt-node {
        position: relative;
        padding: 0.12rem 0;
    }

    .kt-row {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        transition:
            background 0.15s ease,
            box-shadow 0.15s ease;
    }

    

    .kt-row:hover {
        background: #f1f5f9;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    .kt-row:hover::after {
        transform: scaleY(1);
    }

    .kt-icon {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border-radius: 0.5rem;
        flex: 0 0 auto;
        font-size: 1rem;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04);
    }

    .kt-icon--parent {
        background: #fdf5e3;
        color: #d19c2a;
    }

    .kt-icon--leaf {
        background: #e6f4fb;
        color: #0091d4;
    }

    .kt-body {
        min-width: 0;
        flex: 1 1 auto;
    }

    .kt-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
    }

    .kt-name {
        font-weight: 600;
        color: #334155;
        font-size: 0.9rem;
        letter-spacing: -0.01em;
    }

    .kt-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.2rem;
    }

    .kt-chip {
        font-size: 0.72rem;
        font-weight: 500;
        padding: 0.12rem 0.5rem;
        border-radius: 0.375rem;
        background: #e6f4fb;
        color: #006fa5;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    .kt-meta-item {
        font-size: 0.74rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    .kt-meta-empty {
        color: #94a3b8;
        font-size: 0.78rem;
        font-style: italic;
    }

    .kt-badge {
        font-size: 0.64rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: #eaf6ee;
        color: #2c7a47;
        padding: 0.15rem 0.5rem;
        border-radius: 0.375rem;
    }

    .kt-actions {
        display: flex;
        gap: 0.35rem;
        flex: 0 0 auto;
        opacity: 0.55;
        transition: opacity 0.15s ease;
    }

    .kt-row:hover .kt-actions {
        opacity: 1;
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
        background: #3a9d5c;
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
