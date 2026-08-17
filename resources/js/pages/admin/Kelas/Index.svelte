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
                    label:
                        depth > 0
                            ? `${'  '.repeat(depth)}${node.nama}`
                            : node.nama,
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

    function getDepth(id: number | null): number {
        const list = kelas_list ?? [];
        let depth = 0;
        let cur = id;
        while (cur != null) {
            const node = list.find((k) => k.id === cur);
            if (!node) break;
            cur = (node.parent_id ?? null) as number | null;
            depth++;
        }
        return Math.max(0, depth - 1);
    }

    function nextSiblingNumber(): number {
        const list = kelas_list ?? [];
        const siblings = list.filter(
            (k) => (k.parent_id ?? null) === form.parent_id && k.id !== editingId,
        );
        let maxNum = 0;
        let numbered = 0;
        for (const s of siblings) {
            const m = String(s.nama ?? '').match(/[- ](\d+)$/);
            if (m) {
                numbered++;
                maxNum = Math.max(maxNum, Number(m[1]));
            }
        }
        return maxNum > 0 ? maxNum + 1 : numbered + 1;
    }

    function rebuildNama() {
        if (namaTouched) {
            return;
        }
        const list = kelas_list ?? [];
        const parent = list.find((k) => k.id === form.parent_id);
        if (!parent) {
            return;
        }
        const jurusan = (jurusans ?? []).find((j) => j.id === form.jurusan_id);

        if (getDepth(parent.id) === 0) {
            const code = jurusan?.kode || jurusan?.name || '';
            form.nama = [parent.nama, code].filter(Boolean).join('-');
            return;
        }

        if (editingId) {
            const self = (kelas_list ?? []).find((k) => k.id === editingId);
            const m = String(self?.nama ?? '').match(/-(\d+)$/);
            if (m && (self?.parent_id ?? null) === form.parent_id) {
                form.nama = `${parent.nama}-${m[1]}`;
                return;
            }
        }

        form.nama = `${parent.nama}-${nextSiblingNumber()}`;
    }

    function openCreate() {
        editingId = null;
        namaTouched = false;
        form.reset();
        form.active = false;
        form.jurusan_id = null;
        form.guru_id = null;
        form.parent_id = null;
        modalOpen = true;
    }

    function openEdit(node: Record<string, any>) {
        editingId = node.id;
        namaTouched = false;
        form.reset();
        form.nama = node.nama ?? '';
        form.deskripsi = node.deskripsi ?? '';
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
    <li>
        <div
            class="d-flex flex-wrap align-items-center gap-2 px-2 py-2 rounded"
        >
            <i
                class={`bi ${item.children && item.children.length ? 'bi-folder2' : 'bi-mortarboard-fill'} text-secondary`}
            ></i>
            <span class="fs-6 fw-bold text-body text-nowrap">{item.nama}</span>
            {#if depth === 0}
                <span class="badge text-bg-secondary text-xs fw-light"
                    >Tingkat</span
                >
            {:else if depth === 1}
                <span class="badge text-bg-info text-xs fw-light">Jurusan</span>
            {:else}
                <span class="badge text-bg-primary text-xs fw-light">Kelas</span>
            {/if}
            {#if item.active}
                <span class="badge text-bg-success text-xs fw-light">Aktif</span>
            {/if}
            <span
                class="d-flex flex-wrap text-xs align-items-center gap-2 text-secondary small"
            >
                {#if item.jurusan}<span
                        ><i class="bi bi-bookmark-fill"></i>
                        {item.jurusan.name}</span
                    >{/if}
                {#if item.walikelas}<span
                        ><i class="bi bi-person-fill"></i>
                        {item.walikelas.nama_lengkap}</span
                    >{/if}
            </span>
            <span class="ms-auto d-flex gap-2">
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
            <ul class="list-unstyled ps-3">
                {#each item.children as child (child.id)}
                    {@render node(child, depth + 1)}
                {/each}
            </ul>
        {/if}
    </li>
{/snippet}

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 fw-semibold mb-0">Kelas</h1>
        <Button color="primary" size="sm" onclick={openCreate}>
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </Button>
    </div>

    {#if kelas_parent.length}
        <ul class="list-unstyled bg-white border rounded p-2 mb-0">
            {#each kelas_parent as parent (parent.id)}
                {@render node(parent, 0)}
            {/each}
        </ul>
    {:else}
        <div class="text-center text-secondary py-5 border rounded bg-light">
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
                    onParentChange(
                        (e.currentTarget as HTMLSelectElement).value,
                    )}
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
            <Input
                id="nama"
                bind:value={form.nama}
                oninput={onNamaInput}
                invalid={!!form.errors.nama}
            />
            {#if form.errors.nama}
                <small class="text-danger">{form.errors.nama}</small>
            {/if}
            <small class="text-secondary d-block mt-1">
                Nama otomatis mengikuti struktur induk: <code>Tingkat</code>,
                <code>Tingkat-Jurusan</code>, <code>Tingkat-Jurusan-Nomor</code>.
            </small>
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
        </FormGroup>
    </ModalBody>
    <ModalFooter>
        <Button
            color="secondary"
            outline
            onclick={() => (modalOpen = !modalOpen)}>Batal</Button
        >
        <Button color="primary" onclick={submit} disabled={form.processing}>
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
        margin: 0;
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
