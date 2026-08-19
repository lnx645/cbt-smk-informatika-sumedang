<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import {
        FormGroup,
        Label,
        Button,
        Input,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    // Receive existing penilaian data via props
    let { penilaian }: { penilaian: any } = $props();

    const form = useForm({
        nama: penilaian?.nama ?? '',
        deskripsi: penilaian?.deskripsi ?? '',
        tipe: penilaian?.tipe ?? '',
        nilai_maks: penilaian?.nilai_maks ?? 0,
        bobot: penilaian?.bobot ?? 0,
        aktif: penilaian?.aktif ?? false,
    });

    function handleSubmit() {
        form.put(
            PenilaianController.update({ penilaian: penilaian.id })
                .url,
            {
                onSuccess: () =>
                    router.visit(PenilaianController.index().url),
            },
        );
    }
</script>

<PageHeader title="Edit Penilaian" subtitle="Ubah data penilaian" />

<form on:submit|preventDefault={handleSubmit} class="mt-4">
    <FormGroup>
        <Label for="nama" class="text-xs">Nama Penilaian</Label>
        <Input
            id="nama"
            type="text"
            bind:value={form.nama}
            placeholder="Contoh: Ulangan Harian"
            disabled={form.processing}
        />
        {#if form.errors.nama}
            <small class="text-danger d-block mt-1"
                >{form.errors.nama}</small
            >
        {/if}
    </FormGroup>

    <FormGroup>
        <Label for="deskripsi" class="text-xs">Deskripsi</Label>
        <Input
            id="deskripsi"
            type="text"
            bind:value={form.deskripsi}
            placeholder="Optional"
            disabled={form.processing}
        />
        {#if form.errors.deskripsi}
            <small class="text-danger d-block mt-1"
                >{form.errors.deskripsi}</small
            >
        {/if}
    </FormGroup>

    <FormGroup>
        <Label for="tipe" class="text-xs">Tipe</Label>
        <select
            id="tipe"
            bind:value={form.tipe}
            class="form-select"
            disabled={form.processing}
        >
            <option value="" disabled>Pilih tipe</option>
            <option value="kognitif">Kognitif</option>
            <option value="sikap">Sikap</option>
            <option value="tugas">Tugas</option>
            <option value="cbt">CBT</option>
        </select>
        {#if form.errors.tipe}
            <small class="text-danger d-block mt-1"
                >{form.errors.tipe}</small
            >
        {/if}
    </FormGroup>

    <FormGroup>
        <Label for="nilai_maks" class="text-xs">Nilai Maksimum</Label>
        <Input
            id="nilai_maks"
            type="number"
            min="0"
            bind:value={form.nilai_maks}
            placeholder="0"
            disabled={form.processing}
        />
        {#if form.errors.nilai_maks}
            <small class="text-danger d-block mt-1"
                >{form.errors.nilai_maks}</small
            >
        {/if}
    </FormGroup>

    <FormGroup>
        <Label for="bobot" class="text-xs">Bobot (%)</Label>
        <Input
            id="bobot"
            type="number"
            min="0"
            max="100"
            bind:value={form.bobot}
            placeholder="0"
            disabled={form.processing}
        />
        {#if form.errors.bobot}
            <small class="text-danger d-block mt-1"
                >{form.errors.bobot}</small
            >
        {/if}
    </FormGroup>

    <FormGroup class="d-flex align-items-center gap-2">
        <Input
            id="aktif"
            type="checkbox"
            bind:checked={form.aktif}
            disabled={form.processing}
        />
        <Label for="aktif" class="mb-0">Aktif</Label>
        {#if form.errors.aktif}
            <small class="text-danger d-block mt-1"
                >{form.errors.aktif}</small
            >
        {/if}
    </FormGroup>

    <div class="d-flex justify-content-end mt-3">
        <Button
            color="secondary"
            outline
            size="sm"
            on:click={() =>
                router.visit(PenilaianController.index().url)}
            disabled={form.processing}
        >
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </Button>
        <Button
            color="primary"
            size="sm"
            class="ms-2"
            type="submit"
            disabled={form.processing}
        >
            {form.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
        </Button>
    </div>
</form>
