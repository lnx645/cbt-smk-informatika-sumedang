<script lang="ts">
    import 'katex/dist/katex.min.css';
    import { onMount, onDestroy } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import { toast } from 'svelte-sonner';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { confirm } from '@/lib/confirm.svelte';
    import SoalRenderer from '@/components/soal/render/SoalRenderer.svelte';
    import Latex from '@/components/Latex.svelte';
    import {
        KATEGORI_INFO,
        formatDurasi,
        type KategoriUjian,
        type TipeSoal,
    } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    // Mode ujian (/ujian/*) pakai endpoint /ujian/*, bukan /app/siswa/*
    const isModeUjian = typeof window !== 'undefined' && window.location.pathname.startsWith('/ujian/');
    const apiBase = (path: string) => (isModeUjian ? `/ujian${path}` : `/app${path}`);
    const urlWaktuServer = () => (isModeUjian ? '/ujian/waktu-server' : UjianController.waktuServer().url);
    const urlJawaban = (id: number) => (isModeUjian ? `/ujian/${id}/jawaban` : UjianController.simpanJawaban({ ujian: id }).url);
    const urlLapor = (id: number) => (isModeUjian ? `/ujian/${id}/lapor` : UjianController.lapor({ ujian: id }).url);
    const urlSubmit = (id: number) => (isModeUjian ? `/ujian/${id}/submit` : UjianController.submit({ ujian: id }).url);

    type Opsi = { id: number; teks: string };
    type SoalItem = {
        id: number;
        tipe: string;
        pertanyaan: string;
        poin: number;
        media_url: string | null;
        media_type: string | null;
        opsi: Opsi[];
        detail: Record<string, any> | null;
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

    // State jawaban lokal: map soalId -> jawaban (format sesuai tipe, dari SoalRenderer)
    let jawabanMap = $state<Record<number, any>>({});
    let raguMap = $state<Record<number, boolean>>({});
    let indexAktif = $state(0);
    // svelte-ignore state_referenced_locally
    let sisaDetik = $state(pengerjaan.sisa_detik);
    // svelte-ignore state_referenced_locally
    let pelanggaran = $state(pengerjaan.jumlah_pelanggaran);
    let submitting = $state(false);
    // Ukuran font soal (seperti CBT Kemdikbud): 0=kecil, 1=normal, 2=besar, 3=sangat besar
    let fontLevel = $state(typeof localStorage !== 'undefined' ? parseInt(localStorage.getItem('cbt-font-level') ?? '1') : 1);
    let navOpen = $state(false);
    $effect(() => {
        try { localStorage.setItem('cbt-font-level', String(fontLevel)); } catch {}
    });
    const fontSizes = ['0.9rem', '1rem', '1.15rem', '1.3rem'];
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
        // Coba parse jawaban_teks sebagai JSON (untuk tipe kompleks), fallback ke string
        const jt = s.jawaban.jawaban_teks;
        if (jt) {
            try {
                jawabanMap[s.id] = JSON.parse(jt);
            } catch {
                jawabanMap[s.id] = jt;
            }
        } else if (s.jawaban.opsi_dipilih && s.jawaban.opsi_dipilih.length > 0) {
            jawabanMap[s.id] = s.jawaban.opsi_dipilih;
        } else {
            jawabanMap[s.id] = null;
        }
        raguMap[s.id] = s.jawaban.ragu ?? false;
    }
    const LS_KEY = `ujian_${ujian.id}_attempt_${pengerjaan.id}_jawaban`;
    // Merge dengan backup localStorage (jawaban yang belum tersimpan di server)
    muatLocal();

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
            const res = await fetch(urlWaktuServer(), {
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
        soals.filter((s) => {
            const j = jawabanMap[s.id];
            if (j === null || j === undefined) return false;
            if (Array.isArray(j)) return j.length > 0;
            if (typeof j === 'object') return Object.keys(j).length > 0;
            if (typeof j === 'string') return j.trim().length > 0;
            return true;
        }).length,
    );

    function simpanLocal() {
        try {
            localStorage.setItem(LS_KEY, JSON.stringify(jawabanMap));
        } catch {}
    }
    function muatLocal() {
        try {
            const s = localStorage.getItem(LS_KEY);
            if (s) {
                const d = JSON.parse(s);
                for (const k in d) {
                    if (jawabanMap[k] === undefined || jawabanMap[k] === null || jawabanMap[k] === '') {
                        jawabanMap[k] = d[k];
                    }
                }
            }
        } catch {}
    }
    // Debounce: simpan otomatis 1 detik setelah jawaban berubah
    let debounceTimer: any = null;
    let terakhirDisimpan: Record<number, string> = {};
    // Simpan via fetch ringan (non-blocking, tidak pakai Inertia)
    async function simpan(soal: SoalItem) {
        const j = jawabanMap[soal.id];
        // Serialize: object/array -> JSON, string -> langsung
        const jawabanTeks = j === null || j === undefined ? '' : (typeof j === 'string' ? j : JSON.stringify(j));
        // Hemat request: skip jika tidak ada perubahan
        const kunciSimpan = soal.id + ':' + jawabanTeks + ':' + (raguMap[soal.id] ?? false);
        if (terakhirDisimpan[soal.id] === kunciSimpan) return;
        terakhirDisimpan[soal.id] = kunciSimpan;
        try {
            const csrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            const res = await fetch(urlJawaban(ujian.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrf ? decodeURIComponent(csrf[1]) : '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    soal_id: soal.id,
                    opsi_dipilih: [],
                    jawaban_teks: jawabanTeks,
                    ragu: raguMap[soal.id] ?? false,
                }),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
        } catch (e) {
            // Gagal simpan ke server, tapi localStorage sudah aman
            console.warn('Gagal simpan ke server, backup lokal aman:', e);
        }
    }

    function toggleRagu(soal: SoalItem) {
        raguMap[soal.id] = !(raguMap[soal.id] ?? false);
        simpan(soal);
    }

    // === PROTEKSI UJIAN ===
    // Cegah refresh/close tanpa konfirmasi
    $effect(() => {
        const handler = (e: BeforeUnloadEvent) => {
            if (!submitting) {
                e.preventDefault();
            }
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    });
    // Cegah pull-to-refresh di mobile
    $effect(() => {
        let startY = 0;
        const onTouchStart = (e: TouchEvent) => {
            startY = e.touches[0].clientY;
        };
        const onTouchMove = (e: TouchEvent) => {
            const y = e.touches[0].clientY;
            // Jika di posisi paling atas dan geser ke bawah = pull to refresh
            if (window.scrollY === 0 && y > startY + 50) {
                e.preventDefault();
            }
        };
        document.addEventListener('touchstart', onTouchStart, { passive: true });
        document.addEventListener('touchmove', onTouchMove, { passive: false });
        // Cegah overscroll
        document.body.style.overscrollBehavior = 'none';
        return () => {
            document.removeEventListener('touchstart', onTouchStart);
            document.removeEventListener('touchmove', onTouchMove);
            document.body.style.overscrollBehavior = '';
        };
    });
    // Anti inspect element: blokir F12, Ctrl+Shift+I/J, Ctrl+U
    $effect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'F12' ||
                (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) ||
                (e.ctrlKey && e.key.toUpperCase() === 'U')) {
                e.preventDefault();
                lapor('inspect');
            }
        };
        const onContext = (e: Event) => e.preventDefault();
        document.addEventListener('keydown', onKey);
        document.addEventListener('contextmenu', onContext);
        return () => {
            document.removeEventListener('keydown', onKey);
            document.removeEventListener('contextmenu', onContext);
        };
    });

    // Auto-save: pantau SEMUA perubahan jawaban (deep watch)
    // Simpan soal yang berubah (bukan soal aktif) agar tidak salah saat navigasi cepat
    let soalTerakhirDiubah: number | null = null;
    $effect(() => {
        // Baca semua untuk trigger reaktif
        const snapshot = JSON.stringify(jawabanMap) + '|' + JSON.stringify(raguMap);
        if (snapshot && snapshot.length > 10) {
            // Simpan lokal langsung (sinkron, tidak hilang)
            simpanLocal();
            // Cari soal yang berubah (bandingkan dengan terakhirDisimpan)
            for (const s of soals) {
                const j = jawabanMap[s.id];
                const jt = j === null || j === undefined ? '' : (typeof j === 'string' ? j : JSON.stringify(j));
                const kunci = s.id + ':' + jt + ':' + (raguMap[s.id] ?? false);
                if (terakhirDisimpan[s.id] !== kunci) {
                    soalTerakhirDiubah = s.id;
                    break;
                }
            }
            // Debounce server save untuk soal yang berubah
            if (soalTerakhirDiubah) {
                if (debounceTimer) clearTimeout(debounceTimer);
                const sid = soalTerakhirDiubah;
                debounceTimer = setTimeout(() => {
                    const soal = soals.find(s => s.id === sid);
                    if (soal) simpan(soal);
                }, 1000);
            }
        }
    });

    // Otomatis hapus tanda ragu kalau soal sudah diisi
    $effect(() => {
        for (const s of soals) {
            const j = jawabanMap[s.id];
            const adaIsi = j !== null && j !== undefined && (
                Array.isArray(j) ? j.length > 0 :
                typeof j === 'object' ? Object.keys(j).length > 0 :
                String(j).trim().length > 0
            );
            if (adaIsi && raguMap[s.id]) {
                raguMap[s.id] = false;
                simpan(s);
            }
        }
    });

    let lastLapor = 0;
    function lapor(jenis: string) {
        // Debounce: maksimal 1 laporan per 10 detik
        const tNow = Date.now();
        if (tNow - lastLapor < 10000) return;
        lastLapor = tNow;
        const now = Date.now();
        if (now - lastReport < 1000) return;
        lastReport = now;
        pelanggaran += 1;
        router.post(
            urlLapor(ujian.id),
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
        // Hapus cache lokal setelah submit (attempt selesai)
        try { localStorage.removeItem(LS_KEY); } catch {}
        // Flush: batalkan debounce, paksa simpan semua yang belum tersimpan
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
        // Simpan jawaban soal aktif dulu via fetch (tanpa await Inertia yang bisa hang),
        // lalu submit. Cegah jawaban esai terakhir hilang.
        if (soalAktif) {
            const csrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            fetch(urlJawaban(ujian.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrf ? decodeURIComponent(csrf[1]) : '',
                },
                body: JSON.stringify({
                    soal_id: soalAktif.id,
                    opsi_dipilih: [],
                    jawaban_teks: (() => {
                        const j = jawabanMap[soalAktif.id];
                        return j === null || j === undefined ? '' : (typeof j === 'string' ? j : JSON.stringify(j));
                    })(),
                    ragu: raguMap[soalAktif.id] ?? false,
                }),
            })
                .then((res) => {
                    if (!res.ok) {
                        toast.warning('Jawaban terakhir mungkin belum tersimpan (koneksi bermasalah). Tetap kumpulkan? Jawaban sebelumnya sudah aman.');
                    }
                })
                .catch(() => {
                    toast.warning('Koneksi bermasalah saat menyimpan jawaban terakhir. Jawaban sebelumnya sudah aman di server.');
                })
                .finally(() => {
                    router.post(urlSubmit(ujian.id));
                });
        } else {
            router.post(urlSubmit(ujian.id));
        }
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

<div class="ujian-simple">
    <header class="ujian-header sticky-top">
      <div class="d-flex justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <button class="btn btn-outline-secondary d-lg-none nav-toggle" onclick={() => navOpen = !navOpen} title="Navigasi soal">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
            <div class="min-w-0">
                <div class="fw-bold text-truncate ujian-judul">{ujian.judul}</div>
                <div class="progress-mini"><div class="progress-mini-bar" style="width: {(terjawab / soals.length * 100).toFixed(0)}%"></div></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1">
            {#if ujian.maks_pelanggaran > 0}
                <span class="badge {pelanggaran >= ujian.maks_pelanggaran - 1 ? 'bg-danger' : 'bg-secondary'} rounded-pill d-none d-md-inline">
                    <i class="bi bi-shield-exclamation"></i> {pelanggaran}/{ujian.maks_pelanggaran}
                </span>
            {/if}
            <div class="btn-group btn-group-sm" role="group" title="Ukuran huruf">
                <button class="btn btn-outline-secondary" onclick={() => fontLevel = Math.max(0, fontLevel - 1)} disabled={fontLevel <= 0} title="Perkecil">
                    <span style="font-size: 0.75rem; font-weight: bold;">A-</span>
                </button>
                <button class="btn btn-outline-secondary" onclick={() => fontLevel = Math.min(3, fontLevel + 1)} disabled={fontLevel >= 3} title="Perbesar">
                    <span style="font-size: 0.95rem; font-weight: bold;">A+</span>
                </button>
            </div>
            <div class="badge {sisaDetik < 60 ? 'bg-danger' : 'bg-primary'} d-flex align-items-center gap-1 px-2 py-2">
                <i class="bi bi-hourglass-split"></i><span class="fw-bold">{formatDurasi(sisaDetik)}</span>
            </div>
        </div>
      </div>
    </header>
    <div class="ujian-progress"><div class="ujian-progress-bar" style="width: {(terjawab / soals.length * 100).toFixed(1)}%"></div>    <div class="container-soal">
</div>
    <div class="row g-3">
        <div class="col-lg-3 order-2 order-lg-1">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="section-title small text-muted mb-2">Navigasi Soal</div>
                    <div class="d-flex flex-wrap gap-1">
                        {#each soals as s, i (s.id)}
                            {@const jj = jawabanMap[s.id]}
                            {@const sudahIsi = jj !== null && jj !== undefined && (Array.isArray(jj) ? jj.length > 0 : typeof jj === 'object' ? Object.keys(jj).length > 0 : String(jj).trim().length > 0)}
                            {@const ragu = raguMap[s.id] ?? false}
                            <button
                                type="button"
                                class="btn btn-sm nav-soal {i === indexAktif ? 'btn-primary' : ragu ? 'btn-warning' : sudahIsi ? 'btn-success' : 'btn-outline-secondary'}"
                                onclick={() => { indexAktif = i; }}
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

                </CardBody>
            </Card>
        </div>

        <div class="col-lg-9 order-1 order-lg-2">
        <div class="soal-font" style="font-size: {fontSizes[fontLevel]}">
            {#if soalAktif}
                <Card class="border-0 shadow-sm">
                    <CardBody class="p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2">Soal {indexAktif + 1} / {soals.length}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small"><i class="bi bi-award me-1"></i>{soalAktif.poin} poin</span>
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

                        {#if soalAktif}
                            {#key soalAktif.id}
                                {@const dd = (() => {
                                    if (soalAktif.detail && Object.keys(soalAktif.detail).length > 0) return soalAktif.detail;
                                    const t = soalAktif.tipe;
                                    // Konversi opsi lama ke format baru
                                    if (soalAktif.opsi?.length) {
                                        if (t === 'benar_salah') {
                                            // benar_salah butuh pernyataan
                                            return { pernyataan: soalAktif.opsi.map((o: any) => ({ teks: o.teks, jawaban: o.benar ? 'Benar' : 'Salah' })) };
                                        }
                                        return { opsi: soalAktif.opsi };
                                    }
                                    if (t === 'isian_singkat' || t === 'isian') {
                                        return { kunci: [] };
                                    }
                                    return {};
                                })()}
                                <SoalRenderer
                                    tipe={soalAktif.tipe ?? 'pilihan_ganda'}
                                    detail={dd}
                                    bind:jawaban={jawabanMap[soalAktif.id]}
                                    disabled={false}
                                    showKunci={false}
                                />
                            {/key}
                        {:else}
                            <div class="alert alert-warning">Soal tidak ditemukan.</div>
                        {/if}
                        <div class="d-flex gap-2 mt-4">
                            <Button
                                color="light"
                                class="border flex-shrink-0"
                                disabled={indexAktif === 0}
                                onclick={() => { indexAktif -= 1; }}
                            >
                                <i class="bi bi-chevron-left"></i>
                            </Button>
                            <Button
                                color={raguMap[soalAktif.id] ? 'warning' : 'light'}
                                class="border flex-grow-1"
                                onclick={() => toggleRagu(soalAktif)}
                            >
                                <i class="bi bi-flag me-1"></i>Ragu
                            </Button>
                            {#if indexAktif === soals.length - 1}
                                <Button color="success" class="flex-grow-1" onclick={() => submit(false)}>
                                    <i class="bi bi-check-circle me-1"></i>Kumpul
                                </Button>
                            {:else}
                                <Button
                                    color="primary"
                                    class="flex-grow-1"
                                    onclick={() => { indexAktif += 1; }}
                                >
                                    Lanjut<i class="bi bi-chevron-right ms-1"></i>
                                </Button>
                            {/if}
                        </div>
                    </CardBody>
                </Card>
            {/if}
        </div>
    </div>

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
    /* Font size mengikuti level */
    .soal-font {
        line-height: 1.7;
    }
    .soal-font .opsi {
        min-height: 52px;
        padding: 0.75rem !important;
    }
    @media (max-width: 768px) {
        .soal-font {
            line-height: 1.8;
        }
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
    /* === FOKUS MODE === */
    .ujian-fokus {
        min-height: 100vh;
        background: var(--bs-tertiary-bg);
    }
    .ujian-header {
        padding: 0.5rem 0.75rem !important;
        background: #fff !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        z-index: 1020;
    }
    .ujian-header .ujian-judul {
        font-size: 0.95rem !important;
        line-height: 1.2;
    }
    .ujian-header .progress-mini {
        height: 3px !important;
        margin-top: 2px;
    }
    .ujian-header .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
    .ujian-header .badge {
        font-size: 0.85rem !important;
    }
    .ujian-header-old {
        background: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 0.75rem 1rem;
        z-index: 1020;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .ujian-header .timer {
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
    }
    /* Progress bar */
    .ujian-progress {
        height: 4px;
        background: var(--bs-secondary-bg);
    }
    .ujian-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--bs-success), var(--bs-success-bg-subtle));
        transition: width 0.3s ease;
    }
    /* Card soal: fokus, bersih */
    .soal-font .card {
        border: none;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border-radius: 1rem;
    }
    /* Navigasi bawah: sticky di mobile */
    @media (max-width: 991px) {
        .ujian-nav-bottom {
            position: sticky;
            bottom: 0;
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            padding: 0.75rem;
            z-index: 1010;
        }
    }
    /* === SIMPLE CLEAN === */
    .ujian-simple {
        min-height: 100vh;
        background: #f8f9fa;
        overscroll-behavior-y: none;
    }
    :global(html), :global(body) {
        overscroll-behavior: none;
    }
    .ujian-simple .container-soal {
        max-width: 800px;
        margin: 0 auto;
        padding: 1rem;
    }
    @media (max-width: 768px) {
        .ujian-simple .container-soal {
            max-width: 100%;
            padding: 0;
        }
        .ujian-simple .card {
            margin: 0 0 12px 0;
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        .ujian-simple .duo-question-card {
            margin: 0 0 12px 0;
            border-radius: 0;
        }
    }
    /* === DUOLINGO STYLE === */
    .duo-wrap {
        min-height: 100vh;
        background: #fff;
        --duo-green: #58cc02;
        --duo-green-dark: #46a302;
        --duo-blue: #1cb0f6;
        --duo-red: #ff4b4b;
        --duo-yellow: #ffc800;
        --duo-gray: #e5e5e5;
        --duo-text: #3c3c3c;
        --duo-text-light: #777;
    }
    .duo-topbar {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: #fff;
        padding: 12px 16px 8px;
        border-bottom: 2px solid var(--duo-gray);
    }
    .duo-progress-track {
        height: 12px;
        background: var(--duo-gray);
        border-radius: 999px;
        overflow: hidden;
        flex: 1;
    }
    .duo-progress-fill {
        height: 100%;
        background: var(--duo-green);
        border-radius: 999px;
        transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .duo-timer {
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 800;
        font-size: 1rem;
        color: var(--duo-text);
    }
    .duo-timer.danger {
        color: var(--duo-red);
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    .duo-question-card {
        background: #fff;
        border: 2px solid var(--duo-gray);
        border-radius: 20px;
        padding: 24px 20px;
        margin: 16px;
        box-shadow: 0 4px 0 var(--duo-gray);
    }
    /* Mobile: header compact agar tidak kepotong */
    @media (max-width: 768px) {
        .ujian-header .d-flex.justify-content-between {
            gap: 0.25rem !important;
        }
        .ujian-header .ujian-judul {
            font-size: 0.85rem !important;
            max-width: 120px;
        }
        .ujian-header .btn-group-sm .btn {
            padding: 0.2rem 0.35rem !important;
        }
        .ujian-header .badge {
            font-size: 0.8rem !important;
            padding: 0.4rem 0.5rem !important;
            white-space: nowrap;
        }
        .ujian-header .nav-toggle {
            padding: 0.375rem 0.5rem !important;
        }
    }
    /* Break out dari container ExamLayout */
    :global(main.container) {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
    }
    @media (min-width: 769px) {
        :global(main.container) {
            max-width: 1140px !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
    /* Mobile: full-bleed tanpa margin samping */
    @media (max-width: 768px) {
        .duo-question-card {
            margin: 0;
            border-radius: 0;
            border-left: none;
            border-right: none;
            padding: 16px 12px;
        }
        .ujian-simple {
            padding: 0 !important;
        }
        /* Card soal Flowbite jadi full-bleed */
        .col-lg-9 .card {
            border-radius: 0 !important;
            margin: 0 !important;
        }
        .col-lg-9 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .col-lg-9 .card-body {
            padding: 1rem 0.75rem !important;
        }
        /* Navigasi juga full */
        .col-lg-3 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .col-lg-3 .card {
            border-radius: 0 !important;
            border-left: none !important;
            border-right: none !important;
        }
    }
    .duo-bottombar {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 2px solid var(--duo-gray);
        padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
        z-index: 1010;
    }
    .duo-btn {
        flex: 1;
        border: none;
        border-radius: 16px;
        padding: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: transform 0.1s, box-shadow 0.1s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-transform: uppercase;
    }
    .duo-btn:active {
        transform: translateY(2px);
        box-shadow: none !important;
    }
    .duo-btn:disabled {
        opacity: 0.4;
    }
    .duo-btn-prev {
        background: #fff;
        color: var(--duo-blue);
        box-shadow: 0 4px 0 var(--duo-gray);
        border: 2px solid var(--duo-gray);
        flex: 0 0 60px;
    }
    .duo-btn-ragu {
        background: #fff;
        color: #ce8500;
        box-shadow: 0 4px 0 #f0c040;
        border: 2px solid var(--duo-yellow);
    }
    .duo-btn-ragu.active {
        background: var(--duo-yellow);
        color: #fff;
    }
    .duo-btn-next {
        background: var(--duo-green);
        color: #fff;
        box-shadow: 0 4px 0 var(--duo-green-dark);
    }
    .duo-btn-kumpul {
        background: var(--duo-blue);
        color: #fff;
        box-shadow: 0 4px 0 #1899d6;
    }
    /* === MOBILE INNOVATION === */
    .ujian-judul {
        max-width: 140px;
        font-size: 0.95rem;
    }
    .progress-mini {
        height: 3px;
        background: var(--bs-secondary-bg);
        border-radius: 2px;
        margin-top: 2px;
        width: 100px;
    }
    .progress-mini-bar {
        height: 100%;
        background: var(--bs-success);
        border-radius: 2px;
        transition: width 0.3s;
    }
    .nav-toggle {
        width: 38px;
        height: 38px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    .font-btn {
        width: 38px;
        height: 38px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    .font-menu {
        min-width: 160px;
    }
    /* Bottom nav: 3 tombol equal */
    .ujian-nav-bottom {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--bs-border-color);
    }
    .nav-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.8rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1.5px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .nav-btn:disabled {
        opacity: 0.35;
    }
    .nav-btn:not(:disabled):active {
        transform: scale(0.97);
    }
    .nav-prev, .nav-next {
        border-color: var(--bs-primary);
        color: var(--bs-primary);
    }
    .nav-next {
        background: var(--bs-primary);
        color: #fff;
    }
    .nav-ragu {
        border-color: var(--bs-warning);
        color: var(--bs-warning-text-emphasis);
    }
    .nav-ragu.active {
        background: var(--bs-warning);
        color: #000;
    }
    .nav-kumpul {
        background: var(--bs-success);
        border-color: var(--bs-success);
        color: #fff;
    }
    @media (max-width: 576px) {
        .ujian-header {
            padding: 0.6rem 0.75rem;
        }
        .ujian-header .timer {
            font-size: 0.95rem;
            padding: 0.4rem 0.7rem;
        }
    }
    /* Drawer navigasi di mobile */
    .nav-drawer {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 280px;
        background: var(--bs-body-bg);
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.25s ease;
        box-shadow: 2px 0 12px rgba(0,0,0,0.15);
        overflow-y: auto;
    }
    .nav-drawer.open {
        transform: translateX(0);
    }
    .nav-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 1040;
    }
</style>
