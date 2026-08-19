<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import PenilaianCreateModal from '@/components/penilaian/PenilaianCreateModal.svelte';
    import PenilaianEditModal from '@/components/penilaian/PenilaianEditModal.svelte';
    import { confirm } from '@/lib/confirm.svelte';

    type PenilaianItem = {
        id: number;
        nama: string;
        deskripsi: string | null;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
        sumber: 'manual' | 'tugas';
    };

    let { penilaian }: { penilaian: PenilaianItem[] } = $props();

    let createOpen = $state(false);
    let editTarget = $state<PenilaianItem | null>(null);

    async function deletePenilaian(item: PenilaianItem) {
        const ok = await confirm.show({
            title: 'Hapus Penilaian',
            message: `Penilaian "${item.nama}" akan dihapus permanen. Lanjutkan?`,
            confirmText: 'Ya, Hapus',
            color: 'danger',
        });
        if (!ok) return;
        router.delete(
            PenilaianController.destroy({ penilaian: item.id }).url,
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Penilaian"
        subtitle="Kelola jenis penilaian dan nilai siswa."
    >
        {#snippet actions()}
            <Button color="primary" onclick={() => (createOpen = true)}>
                <i class="bi bi-plus-lg me-1"></i>Tambah Penilaian
            </Button>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            {#if penilaian.length === 0}
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clipboard-x display-5 d-block mb-2"></i>
                    <div>Belum ada penilaian.</div>
                </div>
            {:else}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th>Tipe</th>
                                <th>Nilai Maks</th>
                                <th>Bobot</th>
                                <th>Aktif</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each penilaian as p (p.id)}
                                <tr>
                                    <td class="fw-semibold">{p.nama}</td>
                                    <td class="text-muted small">
                                        {p.deskripsi ?? '—'}
                                    </td>
                                    <td>
                                        <Badge
                                            color={
                                                p.sumber === 'tugas'
                                                    ? 'info'
                                                    : 'light'
                                            }
                                            pill
                                        >
                                            {p.sumber === 'tugas'
                                                ? 'Dari Tugas'
                                                : p.tipe}
                                        </Badge>
                                    </td>
                                    <td>{p.nilai_maks}</td>
                                    <td>{p.bobot}</td>
                                    <td>
                                        {#if p.aktif}
                                            <Badge color="success" pill
                                                >Aktif</Badge
                                            >
                                        {:else}
                                            <Badge color="secondary" pill
                                                >Nonaktif</Badge
                                            >
                                        {/if}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a
                                            href={DetailPenilaianController.filterSiswa(
                                                {
                                                    penilaian: p.id,
                                                },
                                            ).url}
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i
                                                class="bi bi-clipboard2-data me-1"
                                            ></i>
                                            Input Nilai
                                        </a>
                                        <a
                                            href={PenilaianController.show(
                                                {
                                                    penilaian: p.id,
                                                },
                                            ).url}
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Lihat
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick={() => (editTarget = p)}
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick={() =>
                                                deletePenilaian(p)}
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </CardBody>
    </Card>

    <PenilaianCreateModal open={createOpen} onClose={() => (createOpen = false)} />
    <PenilaianEditModal
        open={editTarget !== null}
        penilaian={editTarget}
        onClose={() => (editTarget = null)}
    />
</div>