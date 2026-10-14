<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import {
        Button, Card, CardBody, CardHeader, Input, Label,
        Modal, ModalBody, ModalFooter, ModalHeader, Table,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import RuangController from '@/actions/App/Http/Controllers/Admin/RuangController';

    type Ruang = { id: number; kode: string; nama: string; kapasitas: number; lokasi: string | null };
    let { ruangs }: { ruangs: Ruang[] } = $props();

    let modalTerbuka = $state(false);
    let editId: number | null = $state(null);
    const form = useForm({ kode: '', nama: '', kapasitas: 40, lokasi: '' });

    function bukaTambah() {
        editId = null;
        form.reset();
        form.kapasitas = 40;
        modalTerbuka = true;
    }
    function bukaEdit(r: Ruang) {
        editId = r.id;
        form.kode = r.kode; form.nama = r.nama;
        form.kapasitas = r.kapasitas; form.lokasi = r.lokasi ?? '';
        modalTerbuka = true;
    }
    function simpan(e: SubmitEvent) {
        e.preventDefault();
        if (editId) {
            form.put(RuangController.update.url({ ruang: editId }), { onSuccess: () => (modalTerbuka = false) });
        } else {
            form.post(RuangController.store.url(), { onSuccess: () => (modalTerbuka = false) });
        }
    }
    async function hapus(r: Ruang) {
        const ok = await confirm.show({
            title: 'Hapus Ruang',
            message: `Hapus ruang ${r.nama}?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(RuangController.destroy({ ruang: r.id }).url);
    }
</script>

<PageHeader title="Ruang Ujian" />

<Card>
    <CardHeader class="d-flex justify-content-between align-items-center">
        <span>Daftar Ruang ({ruangs.length})</span>
        <Button color="primary" size="sm" onclick={bukaTambah}>+ Ruang</Button>
    </CardHeader>
    <CardBody>
        {#if ruangs.length === 0}
            <EmptyState message="Belum ada ruang" hint="Tambahkan ruang ujian fisik untuk dipakai pada sesi." />
        {:else}
            <Table responsive striped size="sm">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kapasitas</th><th>Lokasi</th><th></th></tr></thead>
                <tbody>
                    {#each ruangs as r}
                        <tr>
                            <td><code>{r.kode}</code></td><td>{r.nama}</td>
                            <td>{r.kapasitas}</td><td>{r.lokasi ?? '–'}</td>
                            <td class="text-end">
                                <Button size="sm" color="outline-secondary" onclick={() => bukaEdit(r)}>Ubah</Button>
                                <Button size="sm" color="outline-danger" onclick={() => hapus(r)}>Hapus</Button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
        {/if}
    </CardBody>
</Card>

<Modal isOpen={modalTerbuka} toggle={() => (modalTerbuka = false)}>
    <ModalHeader>{editId ? 'Ubah' : 'Tambah'} Ruang Ujian</ModalHeader>
    <form onsubmit={simpan}>
        <ModalBody>
            <div class="mb-2"><Label>Kode</Label><Input bind:value={form.kode} placeholder="R101" /></div>
            <div class="mb-2"><Label>Nama</Label><Input bind:value={form.nama} placeholder="Ruang 101" /></div>
            <div class="mb-2"><Label>Kapasitas</Label><Input type="number" min={0} bind:value={form.kapasitas} /></div>
            <div class="mb-2"><Label>Lokasi</Label><Input bind:value={form.lokasi} placeholder="Gedung A lantai 1" /></div>
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" onclick={() => (modalTerbuka = false)}>Batal</Button>
            <Button color="primary" type="submit" disabled={form.processing}>Simpan</Button>
        </ModalFooter>
    </form>
</Modal>
