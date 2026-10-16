<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Alert, Badge, Button, Card, CardBody, CardHeader, Input } from '@sveltestrap/sveltestrap';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    let {
        ujian,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: string;
            matpel: string | null;
            guru: string | null;
            deskripsi: string | null;
            tata_tertib: string | null;
            durasi_menit: number;
            jumlah_soal: number;
            nilai_maks: number;
            butuh_token: boolean;
            wajib_fullscreen: boolean;
            maks_pelanggaran: number;
            ada_menunggu_koreksi: boolean;
        };
    } = $props();

    let setuju = $state(false);
    let token = $state('');
    let memulai = $state(false);
    let peringatan = $state('');

    function mulai() {
        if (!setuju) {
            peringatan = 'Centang persetujuan tata tertib di atas terlebih dahulu.';
            document.getElementById('setuju')?.focus();
            document.getElementById('setuju-box')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        peringatan = '';
        memulai = true;
        const urlMulai = window.location.pathname.startsWith('/ujian/')
            ? `/ujian/mulai/${ujian.id}`
            : `/app/ujian/${ujian.id}/mulai`;
        router.post(
            urlMulai,
            { setuju_tata_tertib: setuju, token: token || undefined },
            { onFinish: () => (memulai = false) },
        );
    }

    const KATEGORI: Record<string, string> = {
        kuis: 'Kuis',
        uts: 'UTS',
        uas: 'UAS',
        usbk: 'USBK',
    };
</script>

<div class="container py-4" style="max-width: 720px">
    <Card class="shadow-sm">
        <CardHeader class="bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Konfirmasi Ujian</h5>
        </CardHeader>
        <CardBody class="p-4">
            <h4 class="fw-bold mb-1">{ujian.judul}</h4>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <Badge color="info">{KATEGORI[ujian.kategori] ?? ujian.kategori}</Badge>
                {#if ujian.matpel}<Badge color="secondary">{ujian.matpel}</Badge>{/if}
                {#if ujian.guru}<span class="small text-muted">Pengampu: {ujian.guru}</span>{/if}
            </div>

            {#if ujian.deskripsi}
                <p class="text-muted">{ujian.deskripsi}</p>
            {/if}

            {#if ujian.ada_menunggu_koreksi}
                <Alert color="warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Nilai pengerjaan sebelumnya belum keluar</strong> (menunggu koreksi guru).
                    Kalau kamu mulai sekarang, kesempatanmu akan berkurang. Pertimbangkan menunggu nilai keluar dulu.
                </Alert>
            {/if}

            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="fw-bold fs-5">{ujian.jumlah_soal}</div>
                        <div class="small text-muted">Soal</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="fw-bold fs-5">{ujian.durasi_menit}<small class="fw-normal"> mnt</small></div>
                        <div class="small text-muted">Durasi</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="fw-bold fs-5">{ujian.nilai_maks}</div>
                        <div class="small text-muted">Nilai Maks</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="fw-bold fs-5">{ujian.maks_pelanggaran || '–'}</div>
                        <div class="small text-muted">Batas Pelanggaran</div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold"><i class="bi bi-list-check me-1"></i>Tata Tertib Pengerjaan</h6>
            <div class="border rounded p-3 mb-3 bg-body-tertiary" style="white-space: pre-wrap;">
                {#if ujian.tata_tertib}{ujian.tata_tertib}{:else}<span class="text-muted fst-italic">Tata tertib belum diatur oleh guru.</span>{/if}
            </div>

            <Alert color="warning" class="small">
                <i class="bi bi-shield-exclamation me-1"></i>
                <strong>Pengawasan aktif selama ujian.</strong>
                Berpindah tab/jendela, keluar dari mode layar penuh, atau membuka aplikasi lain
                akan dicatat sebagai pelanggaran
                {#if ujian.maks_pelanggaran > 0}
                    dan ujian <strong>dihentikan otomatis</strong> setelah {ujian.maks_pelanggaran} pelanggaran.
                {:else}.
                {/if}
                {#if ujian.wajib_fullscreen}
                    Ujian ini <strong>wajib dikerjakan dalam mode layar penuh</strong>.
                {/if}
            </Alert>

            {#if ujian.butuh_token}
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="token-ujian">Token Ujian</label>
                    <Input id="token-ujian" bind:value={token} placeholder="Masukkan token dari pengawas" autocomplete="off" />
                    <div class="form-text">Token diberikan oleh pengawas/guru saat ujian dimulai.</div>
                </div>
            {/if}

            <div class="form-check mb-3" id="setuju-box">
                <input
                    id="setuju"
                    type="checkbox"
                    class="form-check-input"
                    bind:checked={setuju}
                    onchange={() => (peringatan = '')}
                />
                <label class="form-check-label fw-semibold" for="setuju">
                    Saya telah membaca informasi ujian dan <u>menyetujui tata tertib</u> di atas.
                </label>
            </div>

            {#if peringatan}
                <Alert color="danger" class="small py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>{peringatan}
                </Alert>
            {/if}

            <div class="d-flex gap-2">
                <Button color="secondary" outline href={UjianController.index().url}>
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </Button>
                <Button color="primary" class="flex-grow-1 text-center" disabled={memulai} onclick={mulai}>
                    {#if memulai}
                        <span class="spinner-border spinner-border-sm me-1"></span>Memulai…
                    {:else}
                        <i class="bi bi-play-fill me-1"></i>Mulai Ujian
                    {/if}
                </Button>
            </div>
        </CardBody>
    </Card>
</div>
