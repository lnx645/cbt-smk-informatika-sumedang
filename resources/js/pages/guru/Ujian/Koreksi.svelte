<script lang="ts">
    import { inertia, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { TIPE_SOAL_INFO, type TipeSoal } from '@/lib/ujian';
    import HasilUjianController from '@/actions/App/Http/Controllers/Guru/HasilUjianController';

    type Opsi = { id: number; teks: string; benar: boolean };
    type Jawaban = {
        id: number;
        soal_id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        opsi_dipilih: number[] | null;
        jawaban_teks: string | null;
        benar: boolean | null;
        skor: number | null;
        opsi: Opsi[];
    };

    let {
        ujian,
        pengerjaan,
        jawabans,
        pelanggarans,
    }: {
        ujian: { id: number; judul: string; kategori: string; nilai_maks: number };
        pengerjaan: {
            id: number;
            siswa: string;
            nisn: string;
            status: string;
            nilai_objektif: number | null;
            nilai_esai: number | null;
            nilai_total: number | null;
            jumlah_pelanggaran: number;
            submitted_at: string | null;
        };
        jawabans: Jawaban[];
        pelanggarans: { jenis: string; terjadi_at: string }[];
    } = $props();

    const skorForm = useForm({ skor: 0 });

    function nilai(j: Jawaban) {
        skorForm.skor = j.skor ?? 0;
        skorForm.post(
            HasilUjianController.nilaiEsai({
                ujian: ujian.id,
                pengerjaan: pengerjaan.id,
                jawaban: j.id,
            }).url,
            { preserveScroll: true },
        );
    }
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={HasilUjianController.index({ ujian: ujian.id }).url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Rekap Hasil
        </a>
    </div>

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3 d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h1 class="h5 mb-0">{pengerjaan.siswa}</h1>
                <div class="text-muted small">{ujian.judul} · {pengerjaan.nisn}</div>
            </div>
            <div class="text-end">
                <div class="fw-semibold">
                    Nilai: {pengerjaan.nilai_total ?? 'Menunggu koreksi'} / {ujian.nilai_maks}
                </div>
                <div class="small text-muted">
                    Objektif {pengerjaan.nilai_objektif ?? 0} · Esai {pengerjaan.nilai_esai ?? 0}
                </div>
                {#if pengerjaan.jumlah_pelanggaran > 0}
                    <Badge color="danger" pill class="mt-1">
                        {pengerjaan.jumlah_pelanggaran} pelanggaran
                    </Badge>
                {/if}
            </div>
        </CardBody>
    </Card>

    {#if pelanggarans.length}
        <Card class="border rounded-1 shadow-none mb-3">
            <CardBody class="p-3">
                <div class="fw-semibold mb-2">
                    <i class="bi bi-shield-exclamation text-danger me-1"></i>Log Pelanggaran
                </div>
                <ul class="small mb-0">
                    {#each pelanggarans as p (p.terjadi_at + p.jenis)}
                        <li>{p.jenis} — {p.terjadi_at}</li>
                    {/each}
                </ul>
            </CardBody>
        </Card>
    {/if}

    {#each jawabans as j, i (j.id)}
        <Card class="border rounded-1 shadow-none mb-2">
            <CardBody class="p-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-semibold">Soal {i + 1}</span>
                    <Badge color="light" class="text-dark border">
                        {TIPE_SOAL_INFO[j.tipe].label}
                    </Badge>
                    <span class="text-muted small">{j.poin} poin</span>
                    {#if j.benar === true}
                        <Badge color="success" pill>Benar</Badge>
                    {:else if j.benar === false}
                        <Badge color="danger" pill>Salah</Badge>
                    {/if}
                </div>
                <div class="rich-deskripsi mb-2">{@html j.pertanyaan}</div>

                {#if j.opsi.length}
                    <ul class="small mb-2">
                        {#each j.opsi as o (o.id)}
                            <li
                                class:text-success={o.benar}
                                class:fw-semibold={j.opsi_dipilih?.includes(o.id)}
                            >
                                {o.teks}
                                {#if j.opsi_dipilih?.includes(o.id)}
                                    <i class="bi bi-person-check ms-1"></i>
                                {/if}
                                {#if o.benar}
                                    <i class="bi bi-check-circle ms-1 text-success"></i>
                                {/if}
                            </li>
                        {/each}
                    </ul>
                {:else}
                    <div class="border rounded-1 p-2 bg-body-tertiary mb-2 text-pre-wrap">
                        {j.jawaban_teks || '(kosong)'}
                    </div>
                {/if}

                {#if j.tipe === 'esai'}
                    <div class="input-group" style="max-width: 320px">
                        <span class="input-group-text">Skor</span>
                        <input
                            type="number"
                            min="0"
                            max={j.poin}
                            class="form-control"
                            value={j.skor ?? 0}
                            oninput={(e) => (skorForm.skor = Number((e.currentTarget as HTMLInputElement).value))}
                        />
                        <span class="input-group-text">/ {j.poin}</span>
                        <Button color="primary" onclick={() => nilai(j)} disabled={skorForm.processing}>
                            Simpan
                        </Button>
                    </div>
                    {#if skorForm.errors.skor}
                        <div class="text-danger small mt-1">{skorForm.errors.skor}</div>
                    {/if}
                {:else}
                    <div class="small text-muted">Skor otomatis: {j.skor ?? 0}</div>
                {/if}
            </CardBody>
        </Card>
    {/each}
</div>

<style>
    .text-pre-wrap {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
