<script lang="ts">
    import { Card, CardBody, CardHeader, Table, Button, FormGroup, Label, Input } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
import { useForm, router } from '@inertiajs/svelte';

    interface Penilaian {
        id: number;
        nama: string;
        deskripsi: string | null;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
    }

    interface Siswa {
        nisn: string;
        nama_lengkap: string;
    }

    interface Detail {
        id: number;
        penilaian_id: number;
        siswa_nisn: string;
        nilai: number;
        sumber: string;
        keterangan: string | null;
    }

    interface Props {
        penilaian: Penilaian;
        siswa: Siswa;
        detail: Detail;
    }

    let { penilaian, siswa, detail }: Props = $props();

    const form = useForm({
        nilai: detail?.nilai ?? 0,
        sumber: detail?.sumber ?? 'manual',
        keterangan: detail?.keterangan ?? '',
    });

    function handleSubmit() {
        if (detail?.id) {
            form.put(
                DetailPenilaianController.updateNilai({
                    penilaian: penilaian.id,
                    siswa: siswa.nisn,
                }).url,
                {
                    preserveScroll: true,
                },
            );
        } else {
            form.post(
                DetailPenilaianController.storeNilai({
                    penilaian: penilaian.id,
                    siswa: siswa.nisn,
                }).url,
                {
                    preserveScroll: true,
                },
            );
        }
    }
</script>

<PageHeader
    title="Detail Nilai"
    subtitle={`Penilaian: ${penilaian?.nama ?? ''} | Siswa: ${siswa?.nama_lengkap ?? ''}`}
/>

<Card class="mb-4">
    <CardHeader>Informasi Penilaian</CardHeader>
    <CardBody>
        <Table striped class="mb-0">
            <tbody>
                <tr>
                    <th>Nama Penilaian</th>
                    <td>{penilaian?.nama}</td>
                </tr>
                <tr>
                    <th>Tipe</th>
                    <td>{penilaian?.tipe}</td>
                </tr>
                <tr>
                    <th>Nilai Maksimum</th>
                    <td>{penilaian?.nilai_maks}</td>
                </tr>
                <tr>
                    <th>Bobot</th>
                    <td>{penilaian?.bobot}</td>
                </tr>
                <tr>
                    <th>Aktif</th>
                    <td>{penilaian?.aktif ? 'Ya' : 'Tidak'}</td>
                </tr>
            </tbody>
        </Table>
    </CardBody>
</Card>

<Card class="mb-4">
    <CardHeader>Informasi Siswa</CardHeader>
    <CardBody>
        <Table striped class="mb-0">
            <tbody>
                <tr>
                    <th>NISN</th>
                    <td>{siswa?.nisn}</td>
                </tr>
                <tr>
                    <th>Nama Lengkap</th>
                    <td>{siswa?.nama_lengkap}</td>
                </tr>
            </tbody>
        </Table>
    </CardBody>
</Card>

<Card>
    <CardHeader>Input Nilai</CardHeader>
    <CardBody>
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
                    <small class="text-danger d-block mt-1">{form.errors.nilai}</small>
                {/if}
            </FormGroup>

            <FormGroup>
                <Label for="sumber">Sumber</Label>
                <select
                    id="sumber"
                    class="form-select"
                    bind:value={form.sumber}
                    disabled={form.processing}
                >
                    <option value="manual">Manual</option>
                    <option value="tugas">Tugas</option>
                    <option value="cbt">CBT</option>
                </select>
                {#if form.errors.sumber}
                    <small class="text-danger d-block mt-1">{form.errors.sumber}</small>
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
                    <small class="text-danger d-block mt-1">{form.errors.keterangan}</small>
                {/if}
            </FormGroup>

            <div class="d-flex justify-content-end gap-2">
                <Button
                    color="secondary"
                    outline
                    size="sm"
                    onclick={() => router.visit(DetailPenilaianController.filterSiswa({ penilaian: penilaian.id }).url)}
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
