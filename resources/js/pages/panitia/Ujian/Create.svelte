<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';

    let { gurus, kategoris }: {
        gurus: { id: number; nama: string; mapel: string }[];
        kategoris: { value: string; label: string }[];
    } = $props();

    const form = useForm({
        judul: '',
        kategori: 'uts',
        guru_id: '',
        durasi_menit: 90,
        nilai_maks: 100,
    });

    function submit() {
        form.post('/app/panitia/ujian', {
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Buat Ujian Resmi — Panitia</title>
</svelte:head>

<div class="container py-4" style="max-width: 640px">
    <h1 class="h4 mb-1">Buat Ujian Resmi</h1>
    <p class="text-muted mb-4">UTS / UAS / USBK — dibuat oleh panitia, guru hanya mengisi soal.</p>

    <Card>
        <CardHeader>Informasi Ujian</CardHeader>
        <CardBody>
            <div class="mb-3">
                <label class="form-label" for="f-judul">Judul Ujian <span class="text-danger">*</span></label>
                <input id="f-judul" class="form-control" bind:value={form.judul} placeholder="cth: UTS Matematika Kelas XI" />
                {#if form.errors.judul}<div class="text-danger small">{form.errors.judul}</div>{/if}
            </div>

            <div class="mb-3">
                <label class="form-label" for="f-kategori">Kategori <span class="text-danger">*</span></label>
                <select id="f-kategori" class="form-select" bind:value={form.kategori}>
                    {#each kategoris as k}
                        <option value={k.value}>{k.label}</option>
                    {/each}
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="f-guru">Guru Pengampu (pengisi soal) <span class="text-danger">*</span></label>
                <select id="f-guru" class="form-select" bind:value={form.guru_id}>
                    <option value="">— Pilih guru —</option>
                    {#each gurus as g}
                        <option value={g.id}>{g.nama} — {g.mapel}</option>
                    {/each}
                </select>
                {#if form.errors.guru_id}<div class="text-danger small">{form.errors.guru_id}</div>{/if}
                <div class="form-text">Guru yang dipilih akan mengisi bank soal untuk ujian ini.</div>
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label" for="f-durasi">Durasi (menit) <span class="text-danger">*</span></label>
                    <input id="f-durasi" type="number" class="form-control" bind:value={form.durasi_menit} min="1" />
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label" for="f-nilai">Nilai Maks <span class="text-danger">*</span></label>
                    <input id="f-nilai" type="number" class="form-control" bind:value={form.nilai_maks} min="1" max="100" />
                </div>
            </div>

            <Button color="primary" onclick={submit} disabled={form.processing}>
                {form.processing ? 'Menyimpan...' : 'Buat Ujian'}
            </Button>
            <a href="/app/panitia/ujian" class="btn btn-link">Batal</a>
        </CardBody>
    </Card>
</div>
