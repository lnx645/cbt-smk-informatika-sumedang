<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, Modal, ModalBody, ModalFooter, ModalHeader, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Event = {
        id: number; nama: string; kategori: string;
        deskripsi: string | null; status: string;
        tanggal_mulai: string | null; tanggal_selesai: string | null;
    };

    let { events }: { events: { data: Event[]; links: any[] } } = $props();

    let showModal = $state(false);
    let editing = $state<Event | null>(null);
    let form = $state({
        nama: '', kategori: 'uts', deskripsi: '',
        tanggal_mulai: '', tanggal_selesai: '', status: 'draft',
    });
    let errors = $state<Record<string, string>>({});

    function bukaTambah() {
        editing = null;
        form = { nama: '', kategori: 'uts', deskripsi: '', tanggal_mulai: '', tanggal_selesai: '', status: 'draft' };
        errors = {};
        showModal = true;
    }

    function bukaEdit(e: Event) {
        editing = e;
        form = {
            nama: e.nama, kategori: e.kategori, deskripsi: e.deskripsi ?? '',
            tanggal_mulai: (e.tanggal_mulai ?? '').substring(0, 10), tanggal_selesai: (e.tanggal_selesai ?? '').substring(0, 10),
            status: e.status,
        };
        errors = {};
        showModal = true;
    }

    function simpan() {
        const url = editing ? `/app/panitia/event/${editing.id}` : '/app/panitia/event';
        const method = editing ? 'put' : 'post';
        router[method](url, { ...form }, {
            onError: (e) => { errors = e as Record<string, string>; },
            onSuccess: () => { showModal = false; },
        });
    }

    function hapus(e: Event) {
        if (confirm(`Hapus event "${e.nama}"?`)) {
            router.delete(`/app/panitia/event/${e.id}`);
        }
    }

    function kategoriBadge(k: string) {
        return { uts: 'warning', uas: 'danger', usbk: 'info' }[k] ?? 'secondary';
    }

    function statusBadge(s: string) {
        return { draft: 'secondary', aktif: 'success', selesai: 'dark' }[s] ?? 'secondary';
    }
</script>

<svelte:head>
    <title>Kelola Event — Panitia</title>
</svelte:head>

<PageHeader title="Kelola Event" subtitle="Wadah ujian, misal: UTS Ganjil 2026/2027">
    {#snippet actions()}
        <Button color="primary" size="sm" onclick={bukaTambah}>
            <i class="bi bi-plus-circle me-1"></i>Tambah Event
        </Button>
    {/snippet}
</PageHeader>

{#if events.data.length === 0}
    <EmptyState
        icon="bi-flag"
        title="Belum ada event"
        description="Buat event misal: UTS Ganjil 2026/2027, UAS Genap 2026/2027."
    />
{:else}
    <Card>
        <div class="table-responsive">
            <Table hover class="mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th>EVENT</th>
                        <th>TOTAL UJIAN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    {#each events.data as e, i}
                        <tr>
                            <td><Badge color="primary" pill>{i + 1}</Badge></td>
                            <td>
                                <div class="fw-semibold">{e.nama}</div>
                                <div class="text-muted small">Event ujian</div>
                                <div class="mt-1">
                                    <Badge color={kategoriBadge(e.kategori)} pill class="me-1">{e.kategori.toUpperCase()}</Badge>
                                    <Badge color={statusBadge(e.status)} pill>{e.status}</Badge>
                                </div>
                            </td>
                            <td><span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Belum ada ujian</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <Button color="outline-secondary" size="sm" onclick={() => bukaEdit(e)}>
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </Button>
                                    <Button color="outline-secondary" size="sm" onclick={() => hapus(e)}>
                                        <i class="bi bi-trash me-1"></i>Hapus
                                    </Button>
                                    <a use:inertia href="/app/panitia/kartu?event_id={e.id}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person-badge me-1"></i>Kartu ujian
                                    </a>
                                    <Button color="outline-secondary" size="sm">
                                        <i class="bi bi-people me-1"></i>Ruang ujian
                                    </Button>
                                    <Button color="outline-secondary" size="sm">
                                        <i class="bi bi-book me-1"></i>Absensi
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </div>
    </Card>
{/if}

<Modal isOpen={showModal} toggle={() => showModal = false}>
    <ModalHeader toggle={() => showModal = false}>
        {editing ? 'Edit' : 'Tambah'} Event
    </ModalHeader>
    <ModalBody>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="nama">Nama event</label>
            <input id="nama" type="text" class="form-control" bind:value={form.nama} placeholder="UTS Ganjil 2026/2027" />
            {#if errors.nama}<div class="text-danger small">{errors.nama}</div>{/if}
        </div>
        <div class="row mb-3">
            <div class="col-6">
                <label class="form-label fw-semibold" for="kategori">Kategori</label>
                <select id="kategori" class="form-select" bind:value={form.kategori}>
                    <option value="uts">UTS</option>
                    <option value="uas">UAS</option>
                    <option value="usbk">USBK</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold" for="status">Status</label>
                <select id="status" class="form-select" bind:value={form.status}>
                    <option value="draft">Draft</option>
                    <option value="aktif">Aktif</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-6">
                <label class="form-label" for="mulai">Tanggal mulai</label>
                <input id="mulai" type="date" class="form-control" bind:value={form.tanggal_mulai} />
            </div>
            <div class="col-6">
                <label class="form-label" for="selesai">Tanggal selesai</label>
                <input id="selesai" type="date" class="form-control" bind:value={form.tanggal_selesai} />
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="deskripsi">Deskripsi (opsional)</label>
            <textarea id="deskripsi" class="form-control" rows="2" bind:value={form.deskripsi}></textarea>
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => showModal = false}>Batal</Button>
        <Button color="primary" onclick={simpan}>Simpan</Button>
    </ModalFooter>
</Modal>
