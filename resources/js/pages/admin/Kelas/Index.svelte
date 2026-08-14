<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Modal, ModalHeader, ModalBody, ModalFooter, FormGroup, Label, Input, Button } from '@sveltestrap/sveltestrap';
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

    const jurusanOptions = $derived<SelectOption[]>(jurusans.map((j) => ({ value: j.id, label: j.name })));
    const guruOptions = $derived<SelectOption[]>(gurus.map((g) => ({ value: g.id, label: g.nama_lengkap })));

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
            form.submit({ url: route.url, method: route.method }, { onSuccess: () => (modalOpen = false) });
        } else {
            const route = KelasController.store();
            form.submit({ url: route.url, method: route.method }, { onSuccess: () => (modalOpen = false) });
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
    <li>
        <div class="kt-row" style={`padding-left: ${depth * 0.2 + 0.5}rem`}>
            <i class={`kt-icon bi ${item.children && item.children.length ? 'bi-folder2' : 'bi-mortarboard-fill'}`}></i>
            <span class="kt-name">{item.nama}</span>
            {#if item.active}
                <span class="badge text-bg-success ms-1">Aktif</span>
            {/if}
            <span class="kt-meta">
                {#if item.jurusan}<i class="bi bi-bookmark-fill"></i> {item.jurusan.name}{/if}
                {#if item.ruangan}<i class="bi bi-door-closed-fill"></i> Ruang {item.ruangan}{/if}
                {#if item.walikelas}<i class="bi bi-person-fill"></i> {item.walikelas.nama_lengkap}{/if}
            </span>
            <span class="kt-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick={() => openEdit(item)} title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick={() => confirmDelete(item)} title="Hapus">
                    <i class="bi bi-trash"></i>
                </button>
            </span>
        </div>
        {#if item.children && item.children.length}
            <ul class="kt-tree">
                {#each item.children as child (child.id)}
                    {@render node(child, depth + 1)}
                {/each}
            </ul>
        {/if}
    </li>
{/snippet}

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 fw-semibold mb-0">Kelas</h1>
        <Button color="primary" size="sm" onclick={openCreate}>
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </Button>
    </div>

    {#if kelas_parent.length}
        <ul class="kt-tree border rounded p-2">
            {#each kelas_parent as parent (parent.id)}
                {@render node(parent, 0)}
            {/each}
        </ul>
    {:else}
        <div class="text-center text-muted py-5 border rounded bg-light">
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
                onchange={(e) => onParentChange((e.currentTarget as HTMLSelectElement).value)}
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
        </FormGroup>

        <FormGroup>
            <Label for="nama">Nama Kelas</Label>
            <Input id="nama" bind:value={form.nama} oninput={onNamaInput} invalid={!!form.errors.nama} />
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
            <Input id="ruangan" bind:value={form.ruangan} oninput={onRuanganChange} placeholder="Mis. Ruang 1" />
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
            <Input id="deskripsi" type="textarea" bind:value={form.deskripsi} />
        </FormGroup>

        <FormGroup check>
            <Input type="checkbox" bind:checked={form.active} />
            <Label for="active" class="mb-0">Aktif</Label>
        </FormGroup>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" outline onclick={() => (modalOpen = !modalOpen)}>Batal</Button>
        <Button color="primary" onclick={submit} disabled={form.processing}>
            {editingId ? 'Simpan' : 'Tambah'}
        </Button>
    </ModalFooter>
</Modal>

<style>
    .kt-tree {
        list-style: none;
        margin: 0;
    }

    .kt-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.6rem;
        border-radius: 0.4rem;
    }

    .kt-row:hover {
        background: #f1f5f9;
    }

    .kt-name {
        font-weight: 600;
    }

    .kt-icon {
        color: #94a3b8;
        font-size: 1rem;
    }

    .kt-meta {
        color: #64748b;
        font-size: 0.8rem;
    }

    .kt-meta :global(.bi) {
        margin-right: 0.2rem;
    }

    .kt-actions {
        margin-left: auto;
        display: flex;
        gap: 0.35rem;
        opacity: 0.5;
    }

    .kt-row:hover .kt-actions {
        opacity: 1;
    }
</style>
