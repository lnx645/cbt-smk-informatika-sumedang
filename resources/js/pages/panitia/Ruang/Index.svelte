<script lang="ts">
    import { Badge, Button, Card, CardBody, Table, Modal, ModalHeader, ModalBody, ModalFooter } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { router } from '@inertiajs/svelte';

    type Ruang = { id: number; kode: string; nama: string; kapasitas: number; lokasi: string | null };

    let { ruangs }: { ruangs: Ruang[] } = $props();

    let showModal = $state(false);
    let editing = $state<Ruang | null>(null);
    let form = $state({ kode: '', nama: '', kapasitas: 40, lokasi: '' });

    function openTambah() {
        editing = null;
        form = { kode: '', nama: '', kapasitas: 40, lokasi: '' };
        showModal = true;
    }

    function openEdit(r: Ruang) {
        editing = r;
        form = { kode: r.kode, nama: r.nama, kapasitas: r.kapasitas, lokasi: r.lokasi ?? '' };
        showModal = true;
    }

    function simpan() {
        if (editing) {
            router.put(`/app/panitia/ruang/${editing.id}`, form, {
                onSuccess: () => { showModal = false; }
            });
        } else {
            router.post('/app/panitia/ruang', form, {
                onSuccess: () => { showModal = false; }
            });
        }
    }

    function hapus(r: Ruang) {
        if (confirm(`Hapus ruangan ${r.nama}?`)) {
            router.delete(`/app/panitia/ruang/${r.id}`);
        }
    }
</script>

<svelte:head>
    <title>Kelola Ruangan — Panitia</title>
</svelte:head>

<PageHeader title="Kelola Ruangan" subtitle="Daftar ruangan ujian">
    {#snippet actions()}
        <Button color="primary" size="sm" onclick={openTambah}>
            <i class="bi bi-plus-circle me-1"></i>Tambah Ruangan
        </Button>
    {/snippet}
</PageHeader>

{#if ruangs.length === 0}
    <EmptyState icon="bi-door-open" title="Belum ada ruangan" description="Tambahkan ruangan ujian terlebih dahulu." />
{:else}
    <Card>
        <div class="table-responsive">
            <Table hover class="mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kapasitas</th>
                        <th>Lokasi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {#each ruangs as r}
                        <tr>
                            <td><Badge color="secondary">{r.kode}</Badge></td>
                            <td class="fw-semibold">{r.nama}</td>
                            <td class="text-center">{r.kapasitas} orang</td>
                            <td>{r.lokasi ?? '-'}</td>
                            <td class="text-end">
                                <Button color="outline-primary" size="sm" class="me-1" onclick={() => openEdit(r)}>
                                    <i class="bi bi-pencil"></i>
                                </Button>
                                <Button color="outline-danger" size="sm" onclick={() => hapus(r)}>
                                    <i class="bi bi-trash"></i>
                                </Button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        </div>
    </Card>
{/if}

<Modal isOpen={showModal} toggle={() => showModal = false}>
    <ModalHeader toggle={() => showModal = false}>{editing ? 'Edit' : 'Tambah'} Ruangan</ModalHeader>
    <ModalBody>
        <div class="mb-3">
            <label class="form-label" for="kode">Kode</label>
            <input id="kode" class="form-control" bind:value={form.kode} placeholder="LAB-1" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="nama">Nama</label>
            <input id="nama" class="form-control" bind:value={form.nama} placeholder="Lab Komputer 1" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="kapasitas">Kapasitas</label>
            <input id="kapasitas" type="number" class="form-control" bind:value={form.kapasitas} min="1" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="lokasi">Lokasi (opsional)</label>
            <input id="lokasi" class="form-control" bind:value={form.lokasi} placeholder="Gedung A Lt. 2" />
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => showModal = false}>Batal</Button>
        <Button color="primary" onclick={simpan}>Simpan</Button>
    </ModalFooter>
</Modal>
