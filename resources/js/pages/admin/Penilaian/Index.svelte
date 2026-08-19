<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
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

    const { penilaian } = $props();

    const deletePenilaian = (id: number) => {
        if (!confirm('Yakin hapus penilaian ini?')) return;
        router.delete(
            PenilaianController.destroy({ penilaian: id }).url,
        );
    };
</script>

<h1 class="h4 fw-semibold mb-3">Daftar Penilaian</h1>
<table class="table table-hover">
    <thead>
        <tr>
            <th>Nama</th>
            <th>Deskripsi</th>
            <th>Tipe</th>
            <th>Nilai Maks</th>
            <th>Bobot</th>
            <th>Aktif</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        {#each penilaian as p}
            <tr>
                <td>{p.nama}</td>
                <td>{p.deskripsi}</td>
                <td>{p.tipe}</td>
                <td>{p.nilai_maks}</td>
                <td>{p.bobot}</td>
                <td>{p.aktif ? 'Ya' : 'Tidak'}</td>
                <td class="d-flex gap-2">
                    <a
                        href={DetailPenilaianController.filterSiswa({
                            penilaian: p.id,
                        }).url}
                        class="btn btn-sm btn-outline-primary"
                        >Input Nilai</a
                    >
                    <a
                        href={PenilaianController.show({
                            penilaian: p.id,
                        }).url}
                        class="btn btn-sm btn-outline-secondary"
                        >Lihat</a
                    >
                    <a
                        href={PenilaianController.edit({
                            penilaian: p.id,
                        }).url}
                        class="btn btn-sm btn-outline-success">Edit</a
                    >
                    <button
                        class="btn btn-sm btn-outline-danger"
                        on:click={() => deletePenilaian(p.id)}
                        >Hapus</button
                    >
                </td>
            </tr>
        {/each}
    </tbody>
</table>

<a
    href={PenilaianController.create().url}
    class="btn btn-primary mt-3">Tambah Penilaian</a
>
