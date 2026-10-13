<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Card, CardBody, CardTitle } from '@sveltestrap/sveltestrap';
    import IsRole from '@/components/IsRole.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import MatpelGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import GuruTugasController from '@/actions/App/Http/Controllers/Guru/TugasController';
    import GuruUjianController from '@/actions/App/Http/Controllers/Guru/UjianController';
    import GuruBankSoalController from '@/actions/App/Http/Controllers/Guru/BankSoalController';
    import GuruPenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';
    import SiswaUjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';
    import SiswaPenilaianController from '@/actions/App/Http/Controllers/Siswa/PenilaianController';

    type MateriBaru = {
        id: number;
        judul: string;
        matpel: string | null;
        guru: string | null;
        dibuat_pada: string;
    };

    type TugasBelum = {
        id: number;
        judul: string;
        matpel: string | null;
        deadline: string | null;
    };

    type RingkasanMatpel = {
        id: number | null;
        matpel: string;
        total: number;
    };

    type Kutipan = {
        teks: string;
        penulis: string;
    };

    type AksiCepat = {
        href: string;
        label: string;
        deskripsi: string;
        ikon: string;
        varian: 'primary' | 'success' | 'warning' | 'info' | 'violet' | 'rose';
    };

    let {
        nama = null,
        kelas = null,
        tahunAjaran = null,
        kutipan = null,
        materiTerbaru = [],
        ringkasan = [],
        tugasBelum = [],
    }: {
        nama: string | null;
        kelas: string | null;
        tahunAjaran: string | null;
        kutipan: Kutipan | null;
        materiTerbaru: MateriBaru[];
        ringkasan: RingkasanMatpel[];
        tugasBelum: TugasBelum[];
    } = $props();

    const materiUrl = MateriController.index().url;

    const totalMateri = ringkasan.reduce(
        (jumlah, item) => jumlah + item.total,
        0,
    );

    const tanggalHariIni = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const sapaanWaktu = (() => {
        const jam = new Date().getHours();
        if (jam < 11) return 'Selamat pagi';
        if (jam < 15) return 'Selamat siang';
        if (jam < 19) return 'Selamat sore';
        return 'Selamat malam';
    })();

    // Aksi cepat siswa: 4 tujuan utama dalam satu ketukan.
    const aksiSiswa: AksiCepat[] = [
        {
            href: SiswaUjianController.index().url,
            label: 'Kerjakan Ujian',
            deskripsi: 'Ujian CBT yang tersedia',
            ikon: 'bi-pencil-square',
            varian: 'violet',
        },
        {
            href: TugasController.index().url,
            label: 'Tugas Saya',
            deskripsi: `${tugasBelum.length} menunggu dikerjakan`,
            ikon: 'bi-clipboard-check',
            varian: 'warning',
        },
        {
            href: MateriController.index().url,
            label: 'Belajar Materi',
            deskripsi: `${totalMateri} materi tersedia`,
            ikon: 'bi-book-half',
            varian: 'primary',
        },
        {
            href: SiswaPenilaianController.index().url,
            label: 'Nilai Saya',
            deskripsi: 'Lihat hasil belajarmu',
            ikon: 'bi-award',
            varian: 'success',
        },
    ];

    // Aksi cepat guru: 6 alur kerja utama.
    const aksiGuru: AksiCepat[] = [
        {
            href: GuruUjianController.index().url,
            label: 'Kelola Ujian',
            deskripsi: 'Buat & atur ujian CBT',
            ikon: 'bi-pencil-square',
            varian: 'violet',
        },
        {
            href: GuruBankSoalController.index().url,
            label: 'Bank Soal',
            deskripsi: 'Koleksi soal siap pakai',
            ikon: 'bi-collection',
            varian: 'info',
        },
        {
            href: GuruTugasController.index().url,
            label: 'Kelola Tugas',
            deskripsi: 'Beri & periksa tugas',
            ikon: 'bi-clipboard-check',
            varian: 'warning',
        },
        {
            href: GuruMateriController.index().url,
            label: 'Kelola Materi',
            deskripsi: 'Unggah bahan ajar',
            ikon: 'bi-journal-plus',
            varian: 'primary',
        },
        {
            href: GuruPenilaianController.index().url,
            label: 'Input Nilai',
            deskripsi: 'Nilai tugas & ujian',
            ikon: 'bi-award',
            varian: 'success',
        },
        {
            href: MatpelGuruController.index().url,
            label: 'Kelas Saya',
            deskripsi: 'Lihat kelas yang diajar',
            ikon: 'bi-easel2',
            varian: 'rose',
        },
    ];

    // Palet warna solid untuk avatar mapel (deterministik per nama).
    const PALET = [
        'primary',
        'success',
        'info',
        'warning',
        'danger',
        'secondary',
    ] as const;

    function warnaMapel(nama: string): string {
        let hash = 0;
        for (let i = 0; i < nama.length; i++) {
            hash = (hash << 5) - hash + nama.charCodeAt(i);
            hash |= 0;
        }
        return PALET[Math.abs(hash) % PALET.length];
    }

    function inisialMapel(nama: string): string {
        return nama
            .split(/\s+/)
            .slice(0, 2)
            .map((w) => w[0] ?? '')
            .join('')
            .toUpperCase();
    }
</script>

<div class="container-fluid px-0 edu-dash">
    <!-- ══════════ HERO: sapaan + identitas ══════════ -->
    <section class="edu-hero mb-4">
        <div class="edu-hero__pattern" aria-hidden="true">
            <i class="bi bi-mortarboard-fill"></i>
            <i class="bi bi-book-half"></i>
            <i class="bi bi-pencil-fill"></i>
            <i class="bi bi-backpack2-fill"></i>
            <i class="bi bi-globe2"></i>
        </div>
        <div class="edu-hero__inner">
            <img class="edu-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="edu-hero__text">
                <span class="edu-hero__tanggal">
                    <i class="bi bi-calendar3 me-1"></i>{tanggalHariIni}
                </span>
                <h1 class="edu-hero__title">
                    {sapaanWaktu}{nama ? `, ${nama}` : ''}!
                </h1>
                <p class="edu-hero__subtitle">
                    {#if kelas && tahunAjaran}
                        <span class="edu-hero__chip"
                            ><i class="bi bi-people-fill me-1"></i>Kelas {kelas}</span
                        >
                        <span class="edu-hero__chip"
                            ><i class="bi bi-calendar-check me-1"></i>{tahunAjaran}</span
                        >
                    {:else if tahunAjaran}
                        Tahun Ajaran {tahunAjaran}
                    {:else}
                        Selamat datang di portal pembelajaran sekolah.
                    {/if}
                </p>
            </div>
        </div>
    </section>

    <!-- ══════════ AKSI CEPAT SISWA ══════════ -->
    <IsRole role="siswa">
        <section class="mb-4" aria-label="Aksi cepat">
            <h2 class="edu-section-title">
                <i class="bi bi-lightning-charge-fill me-2"></i>Mau ngapain hari ini?
            </h2>
            <div class="edu-aksi-grid">
                {#each aksiSiswa as aksi (aksi.label)}
                    <a
                        use:inertia
                        href={aksi.href}
                        class="edu-aksi edu-aksi--{aksi.varian}"
                    >
                        <span class="edu-aksi__ikon">
                            <i class="bi {aksi.ikon}"></i>
                        </span>
                        <span class="edu-aksi__label">{aksi.label}</span>
                        <span class="edu-aksi__deskripsi">{aksi.deskripsi}</span>
                    </a>
                {/each}
            </div>
        </section>

        {#if kutipan}
            <div class="edu-quote mb-4">
                <i class="bi bi-quote edu-quote__icon"></i>
                <div class="edu-quote__body">
                    <p class="edu-quote__teks">"{kutipan.teks}"</p>
                    <span class="edu-quote__penulis">
                        <i class="bi bi-pencil-fill me-1"></i>{kutipan.penulis}
                    </span>
                </div>
            </div>
        {/if}
    </IsRole>

    <!-- ══════════ AKSI CEPAT GURU ══════════ -->
    <IsRole role="guru">
        <section class="mb-4" aria-label="Aksi cepat guru">
            <h2 class="edu-section-title">
                <i class="bi bi-briefcase-fill me-2"></i>Ruang Kerja Guru
            </h2>
            <p class="edu-section-desc">
                Semua kebutuhan mengajar dalam satu tempat — pilih salah satu untuk mulai.
            </p>
            <div class="edu-aksi-grid edu-aksi-grid--guru">
                {#each aksiGuru as aksi (aksi.label)}
                    <a
                        use:inertia
                        href={aksi.href}
                        class="edu-aksi edu-aksi--{aksi.varian}"
                    >
                        <span class="edu-aksi__ikon">
                            <i class="bi {aksi.ikon}"></i>
                        </span>
                        <span class="edu-aksi__label">{aksi.label}</span>
                        <span class="edu-aksi__deskripsi">{aksi.deskripsi}</span>
                    </a>
                {/each}
            </div>
        </section>
    </IsRole>

    <!-- ══════════ STATISTIK SISWA ══════════ -->
    <IsRole role="siswa">
        {#if ringkasan.length > 0}
            <div class="row g-2 g-md-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="edu-stat edu-stat--primary h-100">
                        <div class="edu-stat__icon">
                            <i class="bi bi-journal-bookmark"></i>
                        </div>
                        <div class="edu-stat__body">
                            <div class="edu-stat__value">{ringkasan.length}</div>
                            <div class="edu-stat__label">Mata Pelajaran</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="edu-stat edu-stat--info h-100">
                        <div class="edu-stat__icon">
                            <i class="bi bi-files"></i>
                        </div>
                        <div class="edu-stat__body">
                            <div class="edu-stat__value">{totalMateri}</div>
                            <div class="edu-stat__label">Total Materi</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="edu-stat edu-stat--warning h-100">
                        <div class="edu-stat__icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="edu-stat__body">
                            <div class="edu-stat__value">{tugasBelum.length}</div>
                            <div class="edu-stat__label">Tugas Menunggu</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="edu-stat edu-stat--success h-100">
                        <div class="edu-stat__icon">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="edu-stat__body">
                            <div class="edu-stat__value">{materiTerbaru.length}</div>
                            <div class="edu-stat__label">Materi Terbaru</div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    </IsRole>

    <!-- ══════════ TUGAS & MATERI SISWA ══════════ -->
    <IsRole role="siswa">
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <Card class="edu-card h-100">
                    <CardBody>
                        <CardTitle class="edu-card__title">
                            <i class="bi bi-clipboard-check text-warning"></i>
                            Tugas Belum Dikerjakan
                            {#if tugasBelum.length > 0}
                                <span class="edu-count">{tugasBelum.length}</span>
                            {/if}
                        </CardTitle>
                        {#if tugasBelum.length > 0}
                            <div class="d-flex flex-column gap-2 mt-2">
                                {#each tugasBelum as item (item.id)}
                                    <a
                                        use:inertia
                                        href={TugasController.show({
                                            tugas: item.id,
                                        }).url}
                                        class="edu-item text-decoration-none"
                                    >
                                        <div class="edu-item__icon edu-item__icon--warning">
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                        <div class="edu-item__body">
                                            <div class="edu-item__title text-truncate">
                                                {item.judul}
                                            </div>
                                            <div class="edu-item__meta">
                                                <span class="edu-item__tag"
                                                    >{item.matpel ?? 'Matpel'}</span
                                                >
                                                {#if item.deadline}
                                                    <span class="edu-item__deadline"
                                                        ><i class="bi bi-alarm me-1"></i
                                                        >{item.deadline}</span
                                                    >
                                                {/if}
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right edu-item__arrow"></i>
                                    </a>
                                {/each}
                            </div>
                        {:else}
                            <div class="edu-empty edu-empty--success mt-2">
                                <i class="bi bi-check2-circle"></i>
                                <span>Semua tugas sudah dikerjakan. Mantap!</span>
                            </div>
                        {/if}
                        <div class="mt-3">
                            <a
                                use:inertia
                                href={TugasController.index().url}
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="bi bi-list-check me-1"></i>Lihat Semua Tugas
                            </a>
                        </div>
                    </CardBody>
                </Card>
            </div>

            <div class="col-12 col-xl-5">
                <Card class="edu-card h-100">
                    <CardBody>
                        <CardTitle class="edu-card__title">
                            <i class="bi bi-clock-history text-primary"></i>
                            Materi Terbaru
                        </CardTitle>
                        {#if materiTerbaru.length > 0}
                            <div class="d-flex flex-column gap-2 mt-2">
                                {#each materiTerbaru as item (item.id)}
                                    <a
                                        use:inertia
                                        href={MateriController.show({
                                            materi: item.id,
                                        }).url}
                                        class="edu-item text-decoration-none"
                                    >
                                        <div class="edu-item__icon edu-item__icon--primary">
                                            <i class="bi bi-file-earmark-richtext"></i>
                                        </div>
                                        <div class="edu-item__body">
                                            <div class="edu-item__title text-truncate">
                                                {item.judul}
                                            </div>
                                            <div class="edu-item__meta">
                                                <span class="edu-item__tag"
                                                    >{item.matpel ?? 'Matpel'}</span
                                                >
                                                <span class="edu-item__meta-text"
                                                    ><i class="bi bi-person me-1"></i
                                                    >{item.guru ?? 'Guru'}</span
                                                >
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right edu-item__arrow"></i>
                                    </a>
                                {/each}
                            </div>
                            <div class="mt-3">
                                <a
                                    use:inertia
                                    href={materiUrl}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-collection me-1"></i>Lihat Semua Materi
                                </a>
                            </div>
                        {:else}
                            <div class="edu-empty mt-2">
                                <i class="bi bi-inbox"></i>
                                <span>Belum ada materi untuk kelasmu.</span>
                            </div>
                        {/if}
                    </CardBody>
                </Card>
            </div>
        </div>
    </IsRole>

    <!-- ══════════ MATERI PER MAPEL ══════════ -->
    {#if ringkasan.length > 0}
        <IsRole role="siswa">
            <h2 class="edu-section-title mb-3">
                <i class="bi bi-grid-1x2 me-2"></i>Jelajahi per Mata Pelajaran
            </h2>
            <div class="row g-3 mb-4">
                {#each ringkasan as item (item.id)}
                    <div class="col-6 col-md-4 col-lg-3">
                        {#if item.id}
                            <a
                                use:inertia
                                href={`${materiUrl}?matpel=${item.id}`}
                                class="text-decoration-none d-block h-100"
                            >
                                <Card class="edu-card edu-mapel h-100">
                                    <CardBody class="p-3 d-flex align-items-center gap-3">
                                        <div
                                            class="edu-avatar edu-avatar--{warnaMapel(
                                                item.matpel,
                                            )}"
                                        >
                                            {inisialMapel(item.matpel)}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">
                                                {item.matpel}
                                            </div>
                                            <div class="text-muted small">
                                                {item.total} materi
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>
                            </a>
                        {:else}
                            <Card class="edu-card h-100">
                                <CardBody class="p-3 d-flex align-items-center gap-3">
                                    <div
                                        class="edu-avatar edu-avatar--{warnaMapel(
                                            item.matpel,
                                        )}"
                                    >
                                        {inisialMapel(item.matpel)}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">
                                            {item.matpel}
                                        </div>
                                        <div class="text-muted small">
                                            {item.total} materi
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        {/if}
                    </div>
                {/each}
            </div>
        </IsRole>
    {/if}
</div>

<style>
    /* ═══════════ HERO ═══════════ */
    .edu-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #2b567a 0%, #4182b3 55%, #6aa3d0 100%);
        color: #fff;
        box-shadow: 0 0.5rem 1.5rem rgba(43, 86, 122, 0.25);
    }

    .edu-hero__pattern {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .edu-hero__pattern i {
        position: absolute;
        color: rgba(255, 255, 255, 0.1);
        font-size: 4rem;
    }

    .edu-hero__pattern i:nth-child(1) { top: -0.5rem; right: 2rem; font-size: 5rem; transform: rotate(-12deg); }
    .edu-hero__pattern i:nth-child(2) { bottom: -1rem; right: 9rem; font-size: 3.2rem; transform: rotate(10deg); }
    .edu-hero__pattern i:nth-child(3) { top: 0.5rem; right: 14rem; font-size: 2.4rem; opacity: 0.7; }
    .edu-hero__pattern i:nth-child(4) { bottom: -0.5rem; left: 40%; font-size: 3.6rem; transform: rotate(-8deg); }
    .edu-hero__pattern i:nth-child(5) { top: -0.8rem; left: 28%; font-size: 2.8rem; opacity: 0.7; }

    .edu-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
        padding: 1.6rem 1.6rem;
    }

    .edu-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 1rem;
        background: #fff;
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .edu-hero__tanggal {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
    }

    .edu-hero__title {
        font-size: 1.65rem;
        font-weight: 800;
        margin: 0.15rem 0 0.3rem;
        line-height: 1.2;
        color: #fff;
    }

    .edu-hero__subtitle {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .edu-hero__chip {
        display: inline-flex;
        align-items: center;
        font-size: 0.8rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        backdrop-filter: blur(4px);
    }

    /* ═══════════ JUDUL SECTION ═══════════ */
    .edu-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 0.15rem;
        color: var(--bs-body-emphasis, var(--bs-body-color));
        display: flex;
        align-items: center;
    }

    .edu-section-title i {
        color: var(--bs-primary);
    }

    .edu-section-desc {
        font-size: 0.88rem;
        color: var(--bs-secondary-color);
        margin-bottom: 1rem;
    }

    /* ═══════════ AKSI CEPAT ═══════════ */
    .edu-aksi-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    @media (min-width: 768px) {
        .edu-aksi-grid {
            grid-template-columns: repeat(4, 1fr);
        }
        .edu-aksi-grid--guru {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (min-width: 1200px) {
        .edu-aksi-grid--guru {
            grid-template-columns: repeat(6, 1fr);
        }
    }

    .edu-aksi {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.35rem;
        padding: 1rem;
        border-radius: 1rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        text-decoration: none;
        color: var(--bs-body-color);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        min-height: 7.5rem;
    }

    .edu-aksi:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.6rem 1.4rem rgba(0, 0, 0, 0.1);
        border-color: var(--bs-primary-border-subtle);
        color: var(--bs-body-color);
    }

    .edu-aksi:active {
        transform: translateY(-1px);
    }

    .edu-aksi__ikon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.85rem;
        font-size: 1.35rem;
        color: #fff;
        margin-bottom: 0.25rem;
    }

    .edu-aksi--primary .edu-aksi__ikon { background: linear-gradient(135deg, #4182b3, #2b567a); }
    .edu-aksi--success .edu-aksi__ikon { background: linear-gradient(135deg, #16a34a, #15803d); }
    .edu-aksi--warning .edu-aksi__ikon { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .edu-aksi--info .edu-aksi__ikon { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .edu-aksi--violet .edu-aksi__ikon { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .edu-aksi--rose .edu-aksi__ikon { background: linear-gradient(135deg, #f43f5e, #be123c); }

    .edu-aksi__label {
        font-weight: 700;
        font-size: 0.95rem;
        line-height: 1.25;
    }

    .edu-aksi__deskripsi {
        font-size: 0.76rem;
        color: var(--bs-secondary-color);
        line-height: 1.35;
    }

    /* ═══════════ QUOTE ═══════════ */
    .edu-quote {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        padding: 1.15rem 1.3rem;
        border-radius: 1rem;
        color: #fff;
        background: linear-gradient(135deg, #6d28d9 0%, #4f46e5 100%);
        box-shadow: 0 0.2rem 0.5rem rgba(107, 78, 255, 0.15);
    }

    .edu-quote__icon {
        font-size: 2rem;
        line-height: 1;
        color: rgba(255, 255, 255, 0.85);
        flex-shrink: 0;
    }

    .edu-quote__teks {
        margin: 0 0 0.3rem;
        font-style: italic;
        font-size: 1rem;
        color: #fff;
    }

    .edu-quote__penulis {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    /* ═══════════ STAT ═══════════ */
    .edu-stat {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem;
        border-radius: 1rem;
        color: #fff;
        box-shadow: 0 0.35rem 0.9rem rgba(0, 0, 0, 0.12);
    }

    .edu-stat--primary { background: linear-gradient(135deg, #4182b3, #2b567a); }
    .edu-stat--info { background: linear-gradient(135deg, #0284c7, #0369a1); }
    .edu-stat--success { background: linear-gradient(135deg, #16a34a, #15803d); }
    .edu-stat--warning { background: linear-gradient(135deg, #ea580c, #c2410c); }

    .edu-stat__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.8rem;
        font-size: 1.3rem;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.2);
    }

    .edu-stat__value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
    }

    .edu-stat__label {
        font-size: 0.76rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
    }

    /* ═══════════ CARD & ITEM ═══════════ */
    .edu-card {
        border-radius: 1rem !important;
        border: 1px solid var(--bs-border-color) !important;
        box-shadow: 0 0.2rem 0.8rem rgba(0, 0, 0, 0.05) !important;
    }

    .edu-card__title {
        font-size: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .edu-count {
        margin-left: auto;
        font-size: 0.75rem;
        font-weight: 700;
        background: var(--bs-warning-bg-subtle);
        color: var(--bs-warning-text-emphasis);
        border-radius: 999px;
        padding: 0.15rem 0.6rem;
    }

    .edu-mapel {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .edu-mapel:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.6rem 1.4rem rgba(0, 0, 0, 0.1) !important;
    }

    .edu-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.7rem 0.8rem;
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.8rem;
        background: var(--bs-tertiary-bg, var(--bs-body-bg));
        transition: background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    }

    .edu-item:hover {
        background: var(--bs-secondary-bg, var(--bs-body-bg));
        transform: translateY(-1px);
        box-shadow: 0 0.3rem 0.7rem rgba(0, 0, 0, 0.08);
        color: var(--bs-body-color);
    }

    .edu-item__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 0.7rem;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
    }

    .edu-item__icon--warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .edu-item__icon--primary { background: linear-gradient(135deg, #4182b3, #2b567a); }

    .edu-item__body {
        min-width: 0;
        flex-grow: 1;
    }

    .edu-item__title {
        font-weight: 600;
        font-size: 0.92rem;
    }

    .edu-item__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.2rem;
        font-size: 0.76rem;
        color: var(--bs-secondary-color);
    }

    .edu-item__tag {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        font-weight: 600;
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary-text-emphasis, var(--bs-body-color));
    }

    .edu-item__deadline,
    .edu-item__meta-text {
        display: inline-flex;
        align-items: center;
    }

    .edu-item__arrow {
        color: var(--bs-secondary-color);
        flex-shrink: 0;
    }

    .edu-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.2rem 0.5rem;
        color: var(--bs-secondary-color);
        font-size: 0.88rem;
        font-weight: 500;
        background: var(--bs-tertiary-bg);
        border: 1px dashed var(--bs-border-color);
        border-radius: 0.8rem;
    }

    .edu-empty--success {
        color: var(--bs-success-text-emphasis);
        background: var(--bs-success-bg-subtle);
        border-color: var(--bs-success-border-subtle);
    }

    /* ═══════════ AVATAR MAPEL ═══════════ */
    .edu-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.85rem;
        font-weight: 700;
        font-size: 1.05rem;
        flex-shrink: 0;
        color: #fff;
    }

    .edu-avatar--primary { background: linear-gradient(135deg, #4182b3, #2b567a); }
    .edu-avatar--secondary { background: linear-gradient(135deg, #64748b, #475569); }
    .edu-avatar--success { background: linear-gradient(135deg, #16a34a, #15803d); }
    .edu-avatar--info { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .edu-avatar--warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .edu-avatar--danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

    /* ═══════════ MOBILE ═══════════ */
    @media (max-width: 575.98px) {
        .edu-hero__inner {
            padding: 1.2rem 1.1rem;
            gap: 0.9rem;
        }
        .edu-hero__logo {
            width: 3.4rem;
            height: 3.4rem;
        }
        .edu-hero__title {
            font-size: 1.3rem;
        }
        .edu-hero__pattern i:nth-child(3),
        .edu-hero__pattern i:nth-child(5) {
            display: none;
        }
        .edu-aksi {
            min-height: 6.8rem;
            padding: 0.85rem;
        }
        .edu-aksi__ikon {
            width: 2.4rem;
            height: 2.4rem;
            font-size: 1.15rem;
        }
        .edu-aksi__label {
            font-size: 0.88rem;
        }
        .edu-stat__value {
            font-size: 1.35rem;
        }
    }
</style>
