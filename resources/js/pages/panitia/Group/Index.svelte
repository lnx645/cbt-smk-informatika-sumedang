<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, Modal, ModalBody, ModalFooter, ModalHeader, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Group = {
        id: number; nama: string; deskripsi: string | null;
        kelas_count: number;
        kelas: { id: number; nama: string }[];
        matpel_id: number | null; guru_id: number | null;
        matpel: { id: number; name: string } | null;
        guru: { id: number; nama: string } | null;
    };

    let { groups, kelasList, matpelList, pengampuList }: {
        groups: { data: Group[]; links: any[] };
        kelasList: { id: number; nama: string }[];
        matpelList: { id: number; name: string }[];
        pengampuList: { matpel_id: number; matpel_nama: string; gurus: { id: number; nama: string; kelas: { id: number; nama: string }[] }[] }[];
    } = $props();

    let showModal = $state(false);
    let editing = $state<Group | null>(null);
    let form = $state({ nama: '', deskripsi: '', kelas_ids: [] as number[] });
    let errors = $state<Record<string, string>>({});

    function bukaTambah() {
        editing = null;
        form = { nama: '', deskripsi: '', kelas_ids: [] };
        errors = {};
        showModal = true;
    }

    function bukaEdit(g: Group) {
        editing = g;
        form = { nama: g.nama, deskripsi: g.deskripsi ?? '', kelas_ids: g.kelas.map(k => k.id) };
        errors = {};
        showModal = true;
    }

    let guruOptions = $derived.by(() => {
        if (!form.matpel_id) return [];
        const found = pengampuList.find(p => p.matpel_id === Number(form.matpel_id));
        return found ? found.gurus : [];
    });

    let kelasOptions = $derived.by(() => {
        if (form.matpel_id && form.guru_id) {
            const gurus = guruOptions;
            const guru = gurus.find((g: any) => g.id === Number(form.guru_id));
            if (guru) return guru.kelas;
        }
        return kelasList;
    });

    function onMatpelChange() {
        form.guru_id = '';
        form.kelas_ids = [];
    }

    function onGuruChange() {
        const gurus = guruOptions;
        const guru = gurus.find((g: any) => g.id === Number(form.guru_id));
        form.kelas_ids = guru ? guru.kelas.map((k: any) => k.id) : [];
    }

    function toggleKelas(id: number) {
        form.kelas_ids = form.kelas_ids.includes(id)
            ? form.kelas_ids.filter(k => k !== id)
            : [...form.kelas_ids, id];
    }

    function simpan() {
        const url = editing ? `/app/panitia/group/${editing.id}` : '/app/panitia/group';
        const method = editing ? 'put' : 'post';
        router[method](url, { ...form }, {
            onError: (e) => { errors = e as Record<string, string>; },
            onSuccess: () => { showModal = false; },
        });
    }

    function hapus(g: Group) {
        if (confirm(`Hapus group "${g.nama}"?`)) {
            router.delete(`/app/panitia/group/${g.id}`);
        }
    }
</script>

<svelte:head>
    <title>Kelola Group — Panitia</title>
</svelte:head>

<PageHeader title="Kelola Group" subtitle="Group berisi sub-group dari kelas, misal: Hari 1, Hari 2">
    {#snippet actions()}
        <Button color="primary" size="sm" onclick={bukaTambah}>
            <i class="bi bi-plus-circle me-1"></i>Tambah Group
        </Button>
    {/snippet}
</PageHeader>

{#if groups.data.length === 0}
    <EmptyState
        icon="bi-collection"
        title="Belum ada group"
        description="Buat group misal: Hari 1, Hari 2, lalu isi dengan kelas."
    />
{:else}
    <div class="row g-3">
        {#each groups.data as g}
            <div class="col-md-6 col-lg-4">
                <Card class="h-100">
                    <CardBody>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">{g.nama}</h6>
                            <Badge color="primary" pill>{g.kelas_count} kelas</Badge>
                        </div>
                        {#if g.matpel}
                            <div class="small mb-1">
                                <Badge color="info" pill class="me-1">{g.matpel.name}</Badge>
                                {#if g.guru}<span class="text-muted">{g.guru.nama}</span>{/if}
                            </div>
                        {:else}
                            <div class="small mb-1"><Badge color="secondary" pill>General</Badge></div>
                        {/if}
                        {#if g.deskripsi}
                            <p class="text-muted small mb-2">{g.deskripsi}</p>
                        {/if}
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            {#each g.kelas as k}
                                <Badge color="light" class="text-dark border">{k.nama}</Badge>
                            {/each}
                        </div>
                        <div class="d-flex gap-2">
                            <Button color="outline-primary" size="sm" onclick={() => bukaEdit(g)}>
                                <i class="bi bi-pencil me-1"></i>Edit
                            </Button>
                            <Button color="outline-danger" size="sm" onclick={() => hapus(g)}>
                                <i class="bi bi-trash me-1"></i>Hapus
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
{/if}

<Modal isOpen={showModal} toggle={() => showModal = false}>
    <ModalHeader toggle={() => showModal = false}>
        {editing ? 'Edit' : 'Tambah'} Group
    </ModalHeader>
    <ModalBody>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="nama">Nama group</label>
            <input id="nama" type="text" class="form-control" bind:value={form.nama} placeholder="Contoh: Hari 1" />
            {#if errors.nama}<div class="text-danger small">{errors.nama}</div>{/if}
        </div>
        <div class="mb-3">
            <label class="form-label" for="deskripsi">Deskripsi (opsional)</label>
            <textarea id="deskripsi" class="form-control" rows="2" bind:value={form.deskripsi}></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Pilih kelas</label>
            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                {#each kelasOptions as k}
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="kelas-{k.id}"
                            checked={form.kelas_ids.includes(k.id)}
                            onchange={() => toggleKelas(k.id)}
                        />
                        <label class="form-check-label" for="kelas-{k.id}">{k.nama}</label>
                    </div>
                {/each}
            </div>
            {#if errors.kelas_ids}<div class="text-danger small">{errors.kelas_ids}</div>{/if}
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => showModal = false}>Batal</Button>
        <Button color="primary" onclick={simpan}>Simpan</Button>
    </ModalFooter>
</Modal>
