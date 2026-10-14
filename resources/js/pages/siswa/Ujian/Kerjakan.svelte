<script lang="ts">
    import 'katex/dist/katex.min.css';
    import { onMount, onDestroy } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { confirm } from '@/lib/confirm.svelte';
    import {
        KATEGORI_INFO,
        formatDurasi,
        type KategoriUjian,
        type TipeSoal,
    } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type Opsi = { id: number; teks: string };
    type SoalItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        media_url: string | null;
        media_type: string | null;
        opsi: Opsi[];
        jawaban: {
            opsi_dipilih: number[] | null;
            jawaban_teks: string | null;
            ragu: boolean;
        };
    };

    let {
        ujian,
        pengerjaan,
        soals,
        sesi = null,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: KategoriUjian;
            wajib_fullscreen: boolean;
            maks_pelanggaran: number;
        };
        pengerjaan: {
            id: number;
            batas_at: string;
            sisa_detik: number;
            jumlah_pelanggaran: number;
        };
        soals: SoalItem[];
        sesi: { nama: string; waktu_selesai: string } | null;
    } = $props();

    // State jawaban lokal (diinisialisasi dari props).
    let jawabanOpsi = $state<Record<number, number[]>>({});
    let jawabanTeks = $state<Record<number, string>>({});
    let raguMap = $state<Record<number, boolean>>({});
    let indexAktif = $state(0);
    // svelte-ignore state_referenced_locally
    let sisaDetik = $state(pengerjaan.sisa_detik);
    // svelte-ignore state_referenced_locally
    let pelanggaran = $state(pengerjaan.jumlah_pelanggaran);
    let submitting = $state(false);
    // Overlay gate: tampil bila wajib fullscreen tapi belum masuk fullscreen.
    // Browser melarang fullscreen otomatis tanpa klik pengguna, jadi harus
    // diminta lewat tombol (user gesture).
    let gateFullscreen = $state(false);

    // Di HP, fullscreen bikin browser auto-rotate ke landscape — jangan paksa.
    function isMobile(): boolean {
        return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    }

    // svelte-ignore state_referenced_locally
    for (const s of soals) {
        jawabanOpsi[s.id] = s.jawaban.opsi_dipilih ?? [];
        jawabanTeks[s.id] = s.jawaban.jawaban_teks ?? '';
        raguMap[s.id] = s.jawaban.ragu ?? false;
    }

    let timer: ReturnType<typeof setInterval>;
    let lastReport = 0;
    // Selisih jam server - jam perangkat (ms). Countdown selalu berlabuh
    // ke waktu server agar tidak bisa diakali lewat pengaturan jam HP.
    let offsetMs = $state(0);

    /**
     * Ambil waktu server lalu hitung ulang sisa detik dari batas_at.
     * Dipanggil saat mount, tiap 30 detik, dan saat tab kembali terlihat.
     */
    async function sinkronWaktu() {
        try {
            const res = await fetch(UjianController.waktuServer().url, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                return;
            }

            const data = await res.json();
            offsetMs = data.unix * 1000 - Date.now();
            const batasMs = new Date(pengerjaan.batas_at).getTime();
            sisaDetik = Math.max(0, Math.round((batasMs - (Date.now() + offsetMs)) / 1000));
        } catch {
            // Gagal sinkron: lanjutkan dengan timer lokal, server tetap
            // menegakkan batas_at saat simpan/submit.
        }
    }

    const soalAktif = $derived(soals[indexAktif]);
    const terjawab = $derived(
        soals.filter(
            (s) =>
                (jawabanOpsi[s.id]?.length ?? 0) > 0 ||
                (jawabanTeks[s.id]?.trim().length ?? 0) > 0,
        ).length,
    );

    function simpan(soal: SoalItem) {
        router.post(
            UjianController.simpanJawaban({ ujian: ujian.id }).url,
            {
                soal_id: soal.id,
                opsi_dipilih: jawabanOpsi[soal.id] ?? [],
                jawaban_teks: jawabanTeks[soal.id] ?? '',
                ragu: raguMap[soal.id] ?? false,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function toggleRagu(soal: SoalItem) {
        raguMap[soal.id] = !(raguMap[soal.id] ?? false);
        simpan(soal);
    }

    function pilihTunggal(soal: SoalItem, opsiId: number) {
        jawabanOpsi[soal.id] = [opsiId];
        simpan(soal);
    }

    function toggleMulti(soal: SoalItem, opsiId: number) {
        const arr = jawabanOpsi[soal.id] ?? [];
        jawabanOpsi[soal.id] = arr.includes(opsiId)
            ? arr.filter((x) => x !== opsiId)
            : [...arr, opsiId];
        simpan(soal);
    }

    function lapor(jenis: string) {
        const now = Date.now();
        if (now - lastReport < 1000) return;
        lastReport = now;
        pelanggaran += 1;
        router.post(
            UjianController.lapor({ ujian: ujian.id }).url,
            { jenis },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    async function submit(auto = false) {
        if (submitting) return;
        if (!auto) {
            const ok = await confirm.show({
                title: 'Kumpulkan Ujian',
                message: `Kamu sudah menjawab ${terjawab} dari ${soals.length} soal. Kumpulkan sekarang?`,
                confirmText: 'Ya, Kumpulkan',
                color: 'primary',
            });
            if (!ok) return;
        }
        submitting = true;
        // Simpan dulu jawaban soal aktif (khusus esai yang belum ke-blur),
        // tunggu selesai, baru submit — cegah jawaban terakhir hilang.
        if (soalAktif) {
            try {
                await router.post(
                    UjianController.simpanJawaban({ ujian: ujian.id }).url,
                    {
                        soal_id: soalAktif.id,
                        opsi_dipilih: jawabanOpsi[soalAktif.id] ?? [],
                        jawaban_teks: jawabanTeks[soalAktif.id] ?? '',
                        ragu: raguMap[soalAktif.id] ?? false,
                    },
                    { preserveState: true, preserveScroll: true, replace: true },
                );
            } catch {
                // Lanjut submit walau simpan gagal; server validasi batas waktu.
            }
        }
        router.post(UjianController.submit({ ujian: ujian.id }).url);
    }

    // ---- Proctoring handlers ----
    function onVisibility() {
        if (document.hidden) {
            lapor('blur');
        } else {
            // Tab kembali terlihat: sinkron ulang ke waktu server.
            sinkronWaktu();
        }
    }
    function onBlur() {
        lapor('blur');
    }
    function onContextMenu(e: Event) {
        // Cegah menu klik kanan, tapi JANGAN hitung pelanggaran:
        // klik kanan tidak disengaja itu hal biasa.
        e.preventDefault();
    }
    function onCopy(e: Event) {
        // Sudah diblokir, jadi tidak perlu dihukum juga (double punishment).
        e.preventDefault();
    }
    function onPaste(e: Event) {
        // Sudah diblokir, jadi tidak perlu dihukum juga (double punishment).
        e.preventDefault();
    }
    function onFullscreenChange() {
        // Di HP, fullscreen bikin browser auto-rotate ke landscape — jangan paksa.
        if (isMobile()) return;
        if (ujian.wajib_fullscreen && !document.fullscreenElement) {
            // Keluar fullscreen di tengah ujian = pelanggaran.
            // Tapi abaikan jika gate masih terbuka (belum pernah masuk).
            if (!gateFullscreen) {
                lapor('exit_fullscreen');
            }
            gateFullscreen = true;
        } else {
            gateFullscreen = false;
        }
    }

    async function masukFullscreen() {
        try {
            if (ujian.wajib_fullscreen && !document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
                gateFullscreen = false;
            }
        } catch {
            // Gagal (mis. ditolak browser) -> tampilkan gate agar pengguna klik manual.
            if (ujian.wajib_fullscreen && !document.fullscreenElement) {
                gateFullscreen = true;
            }
        }
    }

    function lewatiFullscreen() {
        // Siswa memilih lanjut tanpa fullscreen: catat sebagai pelanggaran
        // lalu tutup gate agar bisa mengerjakan.
        lapor('tanpa_fullscreen');
        gateFullscreen = false;
    }

    onMount(() => {
        sinkronWaktu();
        let detikSejakSinkron = 0;
        timer = setInterval(() => {
            sisaDetik -= 1;
            detikSejakSinkron += 1;
            // Sinkron ulang tiap 30 detik agar drift timer lokal terkoreksi.
            if (detikSejakSinkron >= 30) {
                detikSejakSinkron = 0;
                sinkronWaktu();
            }

            if (sisaDetik <= 0) {
                clearInterval(timer);
                submit(true);
            }
        }, 1000);

        document.addEventListener('visibilitychange', onVisibility);
        window.addEventListener('blur', onBlur);
        document.addEventListener('contextmenu', onContextMenu);
        document.addEventListener('copy', onCopy);
        document.addEventListener('paste', onPaste);
        document.addEventListener('fullscreenchange', onFullscreenChange);

        // Di HP jangan paksa fullscreen (bikin auto-rotate landscape).
        if (!isMobile()) {
            masukFullscreen();
        }
    });

    onDestroy(() => {
        clearInterval(timer);
        if (typeof document === 'undefined') return;
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('blur', onBlur);
        document.removeEventListener('contextmenu', onContextMenu);
        document.removeEventListener('copy', onCopy);
        document.removeEventListener('paste', onPaste);
        document.removeEventListener('fullscreenchange', onFullscreenChange);
        if (document.fullscreenElement) {
            document.exitFullscreen?.().catch(() => {});
        }
    });
</script>

<div class="container-fluid px-0 ujian-runtime">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 sticky-top bg-body py-2 border-bottom" style="z-index: 100">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h5 mb-0">{ujian.judul}</h1>
                <Badge color={KATEGORI_INFO[ujian.kategori].color} pill
                    >{KATEGORI_INFO[ujian.kategori].label}</Badge
                >
            </div>
            <div class="text-muted small">
                Terjawab {terjawab}/{soals.length}
            </div>
            {#if sesi}
                <div class="mt-1">
                    <Badge color="info" pill>
                        <i class="bi bi-clock-history me-1"></i>
                        {sesi.nama}
                    </Badge>
                </div>
            {/if}
        </div>
        <div class="d-flex align-items-center gap-2">
            {#if ujian.maks_pelanggaran > 0}
                <Badge color={pelanggaran >= ujian.maks_pelanggaran - 1 ? 'danger' : 'secondary'} pill>
                    <i class="bi bi-shield-exclamation me-1"></i>
                    Pelanggaran {pelanggaran}/{ujian.maks_pelanggaran}
                </Badge>
            {/if}
            <div class="timer badge fs-6" class:bg-danger={sisaDetik < 60} class:bg-primary={sisaDetik >= 60}>
                <i class="bi bi-hourglass-split me-1"></i>{formatDurasi(sisaDetik)}
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-3 order-2 order-lg-1">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="section-title small text-muted mb-2">Navigasi Soal</div>
                    <div class="d-flex flex-wrap gap-1">
                        {#each soals as s, i (s.id)}
                            {@const sudahIsi = (jawabanOpsi[s.id]?.length ?? 0) > 0 || (jawabanTeks[s.id]?.trim().length ?? 0) > 0}
                            {@const ragu = raguMap[s.id] ?? false}
                            <button
                                type="button"
                                class="btn btn-sm nav-soal {i === indexAktif ? 'btn-primary' : ragu ? 'btn-warning' : sudahIsi ? 'btn-success' : 'btn-outline-secondary'}"
                                onclick={() => (indexAktif = i)}
                                title={ragu ? 'Ragu-ragu' : sudahIsi ? 'Sudah diisi' : 'Belum diisi'}
                            >
                                {i + 1}
                            </button>
                        {/each}
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2 small text-muted">
                        <span><span class="badge bg-success">&nbsp;</span> Diisi</span>
                        <span><span class="badge bg-warning">&nbsp;</span> Ragu-ragu</span>
                        <span><span class="badge bg-secondary">&nbsp;</span> Belum diisi</span>
                    </div>
                    <Button color="primary" class="w-100 mt-3 text-center" onclick={() => submit(false)} disabled={submitting}>
                        <i class="bi bi-send me-1"></i>Kumpulkan
                    </Button>
                </CardBody>
            </Card>
        </div>

        <div class="col-lg-9 order-1 order-lg-2">
            {#if soalAktif}
                <Card class="border-0 shadow-sm">
                    <CardBody class="p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2">Soal {indexAktif + 1} / {soals.length}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small"><i class="bi bi-award me-1"></i>{soalAktif.poin} poin</span>
                                <Button
                                    size="sm"
                                    color={raguMap[soalAktif.id] ? 'warning' : 'outline-warning'}
                                    onclick={() => toggleRagu(soalAktif)}
                                >
                                    <i class="bi bi-question-circle me-1"></i>Ragu-ragu
                                </Button>
                            </div>
                        </div>
                        <div class="rich-deskripsi mb-3">
                            {@html soalAktif.pertanyaan}
                        </div>
                        {#if soalAktif.media_url}
                            <div class="mb-3">
                                {#if soalAktif.media_type === 'gambar'}
                                    <img src={soalAktif.media_url} alt="Media soal" class="img-fluid rounded border" style="max-height:320px" />
                                {:else}
                                    <audio controls src={soalAktif.media_url} style="max-width:100%"></audio>
                                {/if}
                            </div>
                        {/if}

                        {#if soalAktif.tipe === 'pg' || soalAktif.tipe === 'benar_salah'}
                            {#each soalAktif.opsi as opsi (opsi.id)}
                                <label class="opsi d-flex align-items-center gap-2 border rounded-1 p-2 mb-2">
                                    <input
                                        type="radio"
                                        name={`soal-${soalAktif.id}`}
                                        checked={jawabanOpsi[soalAktif.id]?.includes(opsi.id)}
                                        onchange={() => pilihTunggal(soalAktif, opsi.id)}
                                    />
                                    <span>{opsi.teks}</span>
                                </label>
                            {/each}
                        {:else if soalAktif.tipe === 'multi'}
                            {#each soalAktif.opsi as opsi (opsi.id)}
                                <label class="opsi d-flex align-items-center gap-2 border rounded-1 p-2 mb-2">
                                    <input
                                        type="checkbox"
                                        checked={jawabanOpsi[soalAktif.id]?.includes(opsi.id)}
                                        onchange={() => toggleMulti(soalAktif, opsi.id)}
                                    />
                                    <span>{opsi.teks}</span>
                                </label>
                            {/each}
                        {:else if soalAktif.tipe === 'isian'}
                            <input
                                class="form-control"
                                placeholder="Ketik jawaban singkat…"
                                bind:value={jawabanTeks[soalAktif.id]}
                                onblur={() => simpan(soalAktif)}
                            />
                        {:else}
                            <textarea
                                class="form-control"
                                rows="6"
                                placeholder="Tulis jawaban esai…"
                                bind:value={jawabanTeks[soalAktif.id]}
                                onblur={() => simpan(soalAktif)}
                            ></textarea>
                        {/if}

                        <div class="d-flex justify-content-between mt-3">
                            <Button
                                color="outline-secondary"
                                disabled={indexAktif === 0}
                                onclick={() => (indexAktif -= 1)}
                            >
                                <i class="bi bi-arrow-left me-1"></i>Sebelumnya
                            </Button>
                            <Button
                                color="outline-secondary"
                                disabled={indexAktif === soals.length - 1}
                                onclick={() => (indexAktif += 1)}
                            >
                                Berikutnya<i class="bi bi-arrow-right ms-1"></i>
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            {/if}
        </div>
    </div>

    {#if gateFullscreen}
        <div class="fullscreen-gate">
            <div class="fullscreen-gate__card">
                <div class="fullscreen-gate__icon">
                    <i class="bi bi-fullscreen"></i>
                </div>
                <h2 class="h5 mb-2">Ujian ini wajib fullscreen</h2>
                <p class="text-muted small mb-3">
                    Klik tombol di bawah untuk masuk mode fullscreen.
                    Keluar dari fullscreen selama ujian dicatat sebagai pelanggaran.
                </p>
                <Button color="primary" class="w-100 mb-2 text-center" onclick={masukFullscreen}>
                    <i class="bi bi-arrows-fullscreen me-1"></i>Masuk Fullscreen
                </Button>
                <button type="button" class="btn btn-link btn-sm text-muted" onclick={lewatiFullscreen}>
                    Lanjutkan tanpa fullscreen (dicatat pelanggaran)
                </button>
            </div>
        </div>
    {/if}
</div>

<style>
    .fullscreen-gate {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(4px);
    }

    .fullscreen-gate__card {
        width: 100%;
        max-width: 380px;
        background: var(--bs-body-bg);
        border-radius: 1rem;
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.3);
    }

    .fullscreen-gate__icon {
        width: 3.5rem;
        height: 3.5rem;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        font-size: 1.5rem;
    }
    .nav-soal {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    .opsi {
        cursor: pointer;
        min-height: 48px;
    }
    .opsi:hover {
        background: var(--bs-primary-bg-subtle);
    }
    .timer {
        font-variant-numeric: tabular-nums;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        color: #fff;
        box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.15);
    }

    .timer.bg-primary {
        background-color: var(--bs-primary) !important;
    }

    .timer.bg-danger {
        background-color: var(--bs-danger) !important;
    }
</style>
