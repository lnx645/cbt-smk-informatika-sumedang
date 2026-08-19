<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import {
        FormGroup,
        Label,
        Input,
        Button,
        Card,
        CardBody,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type PenilaianProps = {
        id: number;
        nama: string;
        deskripsi: string | null;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
        sumber: 'manual' | 'tugas';
    };

    let { penilaian }: { penilaian: PenilaianProps } = $props();

    const form = useForm({
        nama: penilaian?.nama ?? '',
        deskripsi: penilaian?.deskripsi ?? '',
        tipe: penilaian?.tipe ?? '',
        nilai_maks: penilaian?.nilai_maks ?? 0,
        bobot: penilaian?.bobot ?? 0,
        aktif: penilaian?.aktif ?? false,
    });

    function handleSubmit() {
        form.put(PenilaianController.update({ penilaian: penilaian.id }).url, {
            onSuccess: () => router.visit(PenilaianController.index().url),
        });
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Edit Penilaian"
        subtitle="Ubah data jenis penilaian."
    >
        {#snippet actions()}
            <Button
                color="outline-secondary"
                size="sm"
                onclick={() => router.visit(PenilaianController.index().url)}
            >
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </Button>
        {/snippet}
    </PageHeader>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8 col-xxl-7">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-4">
                    <form on:submit|preventDefault={handleSubmit} novalidate>
                        <div class="row g-4">
                            <div class="col-12">
                                <FormGroup>
                                    <Label for="nama" class="fw-semibold">
                                        Nama Penilaian
                                        <span class="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="nama"
                                        type="text"
                                        bind:value={form.nama}
                                        placeholder="Contoh: Ulangan Harian, PTS, PAS"
                                        disabled={form.processing}
                                    />
                                    {#if form.errors.nama}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.nama}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>

                            <div class="col-12">
                                <FormGroup>
                                    <Label for="deskripsi" class="fw-semibold">
                                        Deskripsi
                                    </Label>
                                    <Input
                                        id="deskripsi"
                                        type="text"
                                        bind:value={form.deskripsi}
                                        placeholder="Opsional — misal: Penilaian tengah semester ganjil"
                                        disabled={form.processing}
                                    />
                                    {#if form.errors.deskripsi}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.deskripsi}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>

                            <div class="col-12 col-md-6">
                                <FormGroup>
                                    <Label for="tipe" class="fw-semibold">
                                        Tipe
                                        <span class="text-danger">*</span>
                                    </Label>
                                    <select
                                        id="tipe"
                                        bind:value={form.tipe}
                                        class="form-select"
                                        disabled={form.processing || penilaian?.sumber === 'tugas'}
                                    >
                                        <option value="" disabled>Pilih tipe</option>
                                        <option value="kognitif">Kognitif</option>
                                        <option value="sikap">Sikap</option>
                                        <option value="tugas">Tugas</option>
                                        <option value="cbt">CBT</option>
                                    </select>
                                    {#if penilaian?.sumber === 'tugas'}
                                        <small class="text-muted d-block mt-1">
                                            Penilaian dari tugas guru — tipe tidak bisa diubah.
                                        </small>
                                    {/if}
                                    {#if form.errors.tipe}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.tipe}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>

                            <div class="col-12 col-md-6">
                                <FormGroup>
                                    <Label for="nilai_maks" class="fw-semibold">
                                        Nilai Maksimum
                                        <span class="text-danger">*</span>
                                    </Label>
                                    <Input
                                        id="nilai_maks"
                                        type="number"
                                        min="0"
                                        bind:value={form.nilai_maks}
                                        placeholder="Contoh: 100"
                                        disabled={form.processing || penilaian?.sumber === 'tugas'}
                                    />
                                    <small class="text-muted d-block mt-1">
                                        Batas maksimal nilai yang bisa dimasukkan guru.
                                    </small>
                                    {#if form.errors.nilai_maks}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.nilai_maks}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>

                            <div class="col-12 col-md-6">
                                <FormGroup>
                                    <Label for="bobot" class="fw-semibold">
                                        Bobot (%)
                                    </Label>
                                    <Input
                                        id="bobot"
                                        type="number"
                                        min="0"
                                        max="100"
                                        bind:value={form.bobot}
                                        placeholder="0"
                                        disabled={form.processing}
                                    />
                                    <small class="text-muted d-block mt-1">
                                        Persentase pengaruh ke nilai akhir — 0 jika tidak digunakan.
                                    </small>
                                    {#if form.errors.bobot}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.bobot}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>

                            <div class="col-12 col-md-6">
                                <FormGroup class="d-flex align-items-start gap-2 pt-4">
                                    <Input
                                        id="aktif"
                                        type="checkbox"
                                        bind:checked={form.aktif}
                                        disabled={form.processing}
                                        class="mt-1"
                                    />
                                    <div>
                                        <Label for="aktif" class="fw-semibold mb-0">
                                            Aktif
                                        </Label>
                                        <small class="text-muted d-block">
                                            Nonaktifkan agar jenis penilaian ini tidak tampil
                                            di menu guru.
                                        </small>
                                    </div>
                                    {#if form.errors.aktif}
                                        <small class="text-danger d-block mt-1">
                                            {form.errors.aktif}
                                        </small>
                                    {/if}
                                </FormGroup>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                            <Button
                                color="secondary"
                                outline
                                size="sm"
                                onclick={() => router.visit(PenilaianController.index().url)}
                                disabled={form.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                color="primary"
                                size="sm"
                                type="submit"
                                disabled={form.processing}
                            >
                                <i class="bi bi-check-lg me-1"></i>
                                {form.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    </div>
</div>
