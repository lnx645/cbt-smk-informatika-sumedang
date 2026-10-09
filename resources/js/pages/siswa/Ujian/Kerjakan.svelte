<script lang="ts">
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
        opsi: Opsi[];
        jawaban: {
            opsi_dipilih: number[] | null;
            jawaban_teks: string | null;
        };
    };

    let {
        ujian,
        pengerjaan,
        soals,
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
    } = $props();

    // State jawaban lokal (diinisialisasi dari props).
    let jawabanOpsi = $state<Record<number, number[]>>({});
    let jawabanTeks = $state<Record<number, string>>({});
    let indexAktif = $state(0);
    // svelte-ignore state_referenced_locally
    let sisaDetik = $state(pengerjaan.sisa_detik);
    // svelte-ignore state_referenced_locally
    let pelanggaran = $state(pengerjaan.jumlah_pelanggaran);
    let submitting = $state(false);

    // svelte-ignore state_referenced_locally
    for (const s of soals) {
        jawabanOpsi[s.id] = s.jawaban.opsi_dipilih ?? [];
        jawabanTeks[s.id] = s.jawaban.jawaban_teks ?? '';
    }

    let timer: ReturnType<typeof setInterval>;
    let lastReport = 0;

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
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
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
        router.post(UjianController.submit({ ujian: ujian.id }).url);
    }

    // ---- Proctoring handlers ----
    function onVisibility() {
        if (document.hidden) lapor('blur');
    }
    function onBlur() {
        lapor('blur');
    }
    function onContextMenu(e: Event) {
        e.preventDefault();
        lapor('contextmenu');
    }
    function onCopy(e: Event) {
        e.preventDefault();
        lapor('copy');
    }
    function onPaste(e: Event) {
        e.preventDefault();
        lapor('paste');
    }
    function onFullscreenChange() {
        if (ujian.wajib_fullscreen && !document.fullscreenElement) {
            lapor('exit_fullscreen');
        }
    }

    async function masukFullscreen() {
        try {
            if (ujian.wajib_fullscreen && !document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            }
        } catch {
            // Diabaikan; pelanggaran tetap tercatat lewat fullscreenchange.
        }
    }

    onMount(() => {
        timer = setInterval(() => {
            sisaDetik -= 1;
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

        masukFullscreen();
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
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
                            <button
                                type="button"
                                class="btn btn-sm nav-soal {i === indexAktif ? 'btn-primary' : (jawabanOpsi[s.id]?.length || jawabanTeks[s.id]?.trim()) ? 'btn-success' : 'btn-outline-secondary'}"
                                onclick={() => (indexAktif = i)}
                            >
                                {i + 1}
                            </button>
                        {/each}
                    </div>
                    <Button color="primary" class="w-100 mt-3" onclick={() => submit(false)} disabled={submitting}>
                        <i class="bi bi-send me-1"></i>Kumpulkan
                    </Button>
                </CardBody>
            </Card>
        </div>

        <div class="col-lg-9 order-1 order-lg-2">
            {#if soalAktif}
                <Card class="border rounded-1 shadow-none">
                    <CardBody class="p-3 p-md-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">Soal {indexAktif + 1}</span>
                            <span class="text-muted small">{soalAktif.poin} poin</span>
                        </div>
                        <div class="rich-deskripsi mb-3">
                            {@html soalAktif.pertanyaan}
                        </div>

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
</div>

<style>
    .nav-soal {
        width: 40px;
    }
    .opsi {
        cursor: pointer;
    }
    .opsi:hover {
        background: var(--bs-primary-bg-subtle);
    }
    .timer {
        font-variant-numeric: tabular-nums;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        color: #fff;
        box-shadow: 0 0.2rem 0.5rem rgba(37, 99, 235, 0.25);
    }

    .timer.bg-primary {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }

    .timer.bg-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
</style>
