<script lang="ts">
    import { Card, CardBody, Table, Button, FormGroup, Label, Input } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { useForm, router } from '@inertiajs/svelte';

    interface Penilaian {
        id: number;
        nama: string;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
    }

    interface GuruKelasInfo {
        id: number;
        kelas: string | null;
        matpel: string | null;
    }

    interface Siswa {
        nisn: string;
        nama_lengkap: string;
    }

    interface Detail {
        id: number;
        nilai: number;
        keterangan: string | null;
    }

    interface Props {
        penilaian: Penilaian;
        guruKelas: GuruKelasInfo;
        siswa: Siswa;
        detail: Detail | null;
    }

    let { penilaian, guruKelas, siswa, detail }: Props = $props();

    const form = useForm({
        nilai: detail?.nilai ?? '',
        keterangan: detail?.keterangan ?? '',
    });

    function handleSubmit() {
        form.post(
            DetailPenilaianController.storeNilai({
                penilaian: penilaian.id,
                guruKelas: guruKelas.id,
                siswa: siswa.nisn,
            }).url,
            {
                preserveScroll: true,
            },
        );
    }
</script>

<PageHeader
    title="Detail Nilai"
    subtitle={`${penilaian?.nama ?? ''} | ${siswa?.nama_lengkap ?? ''}`}
/>

<Card class="mb-4 border rounded-1 shadow-none">
    <CardBody class="p-3">
        <Table borderless class="mb-0 small">
            <tbody>
                <tr>
                    <th class="text-muted w-25">Penilaian</th>
                    <td class="fw-semibold">{penilaian?.nama}</td>
                </tr>
                <tr>
                    <th class="text-muted">Penugasan</th>
                    <td>
                        {guruKelas?.kelas ?? 'Kelas'} · {guruKelas?.matpel ??
                            'Matpel'}
                    </td>
                </tr>
                <tr>
                    <th class="text-muted">Nilai Maksimum</th>
                    <td>{penilaian?.nilai_maks}</td>
                </tr>
                <tr>
                    <th class="text-muted">NISN</th>
                    <td>{siswa?.nisn}</td>
                </tr>
                <tr>
                    <th class="text-muted">Nama Siswa</th>
                    <td>{siswa?.nama_lengkap}</td>
                </tr>
            </tbody>
        </Table>
    </CardBody>
</Card>

<Card class="border rounded-1 shadow-none">
    <CardBody class="p-3">
        <div class="fw-semibold mb-3">Input Nilai</div>
        <form on:submit|preventDefault={handleSubmit}>
            <FormGroup>
                <Label for="nilai">Nilai</Label>
                <Input
                    id="nilai"
                    type="number"
                    min="0"
                    max={penilaian?.nilai_maks}
                    bind:value={form.nilai}
                    disabled={form.processing}
                />
                {#if form.errors.nilai}
                    <small class="text-danger d-block mt-1"
                        >{form.errors.nilai}</small
                    >
                {/if}
            </FormGroup>

            <FormGroup>
                <Label for="keterangan">Keterangan</Label>
                <Input
                    id="keterangan"
                    type="text"
                    bind:value={form.keterangan}
                    placeholder="Opsional"
                    disabled={form.processing}
                />
                {#if form.errors.keterangan}
                    <small class="text-danger d-block mt-1"
                        >{form.errors.keterangan}</small
                    >
                {/if}
            </FormGroup>

            <div class="d-flex justify-content-end gap-2">
                <Button
                    color="secondary"
                    outline
                    size="sm"
                    onclick={() =>
                        router.visit(
                            DetailPenilaianController.filterSiswa({
                                penilaian: penilaian.id,
                            }).url,
                        )}
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Kembali
                </Button>
                <Button
                    color="primary"
                    size="sm"
                    type="submit"
                    disabled={form.processing}
                >
                    {form.processing ? 'Menyimpan...' : 'Simpan Nilai'}
                </Button>
            </div>
        </form>
    </CardBody>
</Card>