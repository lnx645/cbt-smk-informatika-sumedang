<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Card, CardBody, CardTitle } from '@sveltestrap/sveltestrap';
    import IsRole from '@/components/IsRole.svelte';
    import MateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import MatpelGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';

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

<div class="container-fluid px-0">
    <!-- Hero dengan logo sekolah + sapaan -->
    <div class="dash-hero mb-4">
        <div class="dash-hero__inner">
            <img class="dash-hero__logo" src="/logo.webp" alt="Logo sekolah" />
            <div class="dash-hero__text">
                <span class="dash-hero__eyebrow">Dashboard</span>
                <h1 class="dash-hero__title">
                    {nama ? `Halo, ${nama}! 👋` : 'Selamat datang!'}
                </h1>
                <p class="dash-hero__subtitle">
                    {#if kelas && tahunAjaran}
                        Kamu di kelas {kelas} · Tahun Ajaran {tahunAjaran}.
                    {:else if tahunAjaran}
                        Tahun Ajaran {tahunAjaran}.
                    {:else}
                        Selamat datang di aplikasi sekolah.
                    {/if}
                </p>
            </div>
        </div>
    </div>

    <IsRole role="siswa">
        {#if kutipan}
            <div class="dash-quote mb-4">
                <i class="bi bi-quote dash-quote__icon"></i>
                <svg
                    class="dash-quote__bg"
                    viewBox="0 0 200 200"
                    aria-hidden="true"
                >
                    <path
                        d="M 40 150 Q 20 120 40 90 Q 60 60 90 70"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="6"
                        stroke-linecap="round"
                        opacity="0.28"
                    />
                </svg>
                <div class="dash-quote__body">
                    <p class="dash-quote__teks">"{kutipan.teks}"</p>
                    <span class="dash-quote__penulis"
                        ><i class="bi bi-pencil-fill me-1"></i>{kutipan.penulis}</span
                    >
                </div>
            </div>
        {/if}
    </IsRole>

    {#if ringkasan.length > 0}
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--primary h-100">
                    <div class="stat-icon">
                        <i class="bi bi-journal-bookmark"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{ringkasan.length}</div>
                        <div class="stat-label">Mata Pelajaran</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--info h-100">
                    <div class="stat-icon">
                        <i class="bi bi-files"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{totalMateri}</div>
                        <div class="stat-label">Total Materi</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--success h-100">
                    <div class="stat-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{tugasBelum.length}</div>
                        <div class="stat-label">Tugas Menunggu</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-card--warning h-100">
                    <div class="stat-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-value">{materiTerbaru.length}</div>
                        <div class="stat-label">Materi Terbaru</div>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    <IsRole role="siswa">
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-7">
                <Card class="border rounded-1 shadow-sm h-100">
                    <CardBody>
                        <CardTitle
                            class="h6 fw-semibold d-flex align-items-center gap-2"
                        >
                            <i class="bi bi-clipboard-check text-primary"></i>
                            Tugas Belum Dikerjakan
                        </CardTitle>
                        {#if tugasBelum.length > 0}
                            <div class="d-flex flex-column gap-2 mt-2">
                                {#each tugasBelum as item (item.id)}
                                    <a
                                        use:inertia
                                        href={TugasController.show({
                                            tugas: item.id,
                                        }).url}
                                        class="task-item text-decoration-none"
                                    >
                                        <div class="task-item__icon">
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                        <div class="task-item__body">
                                            <div
                                                class="task-item__title text-truncate"
                                            >
                                                {item.judul}
                                            </div>
                                            <div class="task-item__meta">
                                                <span class="task-item__matpel"
                                                    >{item.matpel ?? 'Matpel'}</span
                                                >
                                                {#if item.deadline}
                                                    <span class="task-item__deadline"
                                                        ><i
                                                            class="bi bi-alarm me-1"
                                                        ></i
                                                        >{item.deadline}</span
                                                    >
                                                {/if}
                                            </div>
                                        </div>
                                        <i
                                            class="bi bi-chevron-right task-item__arrow"
                                        ></i>
                                    </a>
                                {/each}
                            </div>
                        {:else}
                            <div class="task-empty mt-2">
                                <i class="bi bi-check2-circle"></i>
                                <span
                                    >Tidak ada tugas yang belum dikerjakan.
                                    Mantap!</span
                                >
                            </div>
                        {/if}
                        <div class="mt-3">
                            <a
                                use:inertia
                                href={TugasController.index().url}
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="bi bi-list-check me-1"></i>Lihat
                                Semua Tugas
                            </a>
                        </div>
                    </CardBody>
                </Card>
            </div>

            <div class="col-12 col-xl-5">
                <Card class="border rounded-1 shadow-sm h-100">
                    <CardBody>
                        <CardTitle
                            class="h6 fw-semibold d-flex align-items-center gap-2"
                        >
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
                                        class="task-item task-item--materi text-decoration-none"
                                    >
                                        <div class="task-item__icon">
                                            <i class="bi bi-file-earmark-richtext"></i>
                                        </div>
                                        <div class="task-item__body">
                                            <div
                                                class="task-item__title text-truncate"
                                            >
                                                {item.judul}
                                            </div>
                                            <div class="task-item__meta">
                                                <span class="task-item__matpel"
                                                    >{item.matpel ?? 'Matpel'}</span
                                                >
                                                <span class="task-item__deadline"
                                                    ><i class="bi bi-person me-1"></i
                                                    >{item.guru ?? 'Guru'}</span
                                                >
                                                <span class="task-item__date"
                                                    >{item.dibuat_pada}</span
                                                >
                                            </div>
                                        </div>
                                        <i
                                            class="bi bi-chevron-right task-item__arrow"
                                        ></i>
                                    </a>
                                {/each}
                            </div>
                            <div class="mt-3">
                                <a
                                    use:inertia
                                    href={materiUrl}
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-collection me-1"
                                    ></i>Lihat Semua Materi
                                </a>
                            </div>
                        {:else}
                            <div class="task-empty mt-2">
                                <i class="bi bi-inbox"></i>
                                <span>Belum ada materi untuk kelasmu.</span>
                            </div>
                        {/if}
                    </CardBody>
                </Card>
            </div>
        </div>
    </IsRole>

    {#if ringkasan.length > 0}
        <div class="d-flex align-items-center gap-2 mb-3">
            <h2 class="section-title mb-0">
                <i class="bi bi-grid-1x2 me-2"></i>Materi per Mata Pelajaran
            </h2>
        </div>
        <div class="row g-3 mb-4">
            {#each ringkasan as item (item.id)}
                <div class="col-6 col-md-4 col-lg-3">
                    {#if item.id}
                        <a
                            use:inertia
                            href={`${materiUrl}?matpel=${item.id}`}
                            class="text-decoration-none d-block h-100"
                        >
                            <Card
                                class="border rounded-1 shadow-sm h-100"
                            >
                                <CardBody class="p-3 d-flex align-items-center gap-3">
                                    <div
                                        class="dash-avatar dash-avatar--{warnaMapel(
                                            item.matpel,
                                        )}"
                                    >
                                        {inisialMapel(item.matpel)}
                                    </div>
                                    <div class="min-w-0">
                                        <div
                                            class="fw-semibold text-truncate"
                                        >
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
                        <Card
                            class="border rounded-1 shadow-sm h-100"
                        >
                            <CardBody class="p-3 d-flex align-items-center gap-3">
                                <div
                                    class="dash-avatar dash-avatar--{warnaMapel(
                                        item.matpel,
                                    )}"
                                >
                                    {inisialMapel(item.matpel)}
                                </div>
                                <div class="min-w-0">
                                    <div
                                        class="fw-semibold text-truncate"
                                    >
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
    {/if}

    <IsRole role="guru">
        <div class="guru-panel mt-4">
            <div class="guru-panel__body">
                <i class="bi bi-mortarboard guru-panel__icon"></i>
                <div class="guru-panel__text">
                    <h2 class="guru-panel__title">
                        Selamat bekerja, {nama ?? 'Guru'}! 👔
                    </h2>
                    <p class="guru-panel__subtitle">
                        Kelola materi pembelajaran untuk kelas yang kamu ajar.
                    </p>
                </div>
            </div>
            <div class="guru-panel__actions">
                <a
                    use:inertia
                    href={GuruMateriController.index().url}
                    class="btn btn-sm btn-primary"
                >
                    <i class="bi bi-files me-1"></i>Kelola Materi
                </a>
                <a
                    use:inertia
                    href={MatpelGuruController.index().url}
                    class="btn btn-sm btn-outline-light"
                >
                    <i class="bi bi-journal-bookmark me-1"></i>Matpel Saya
                </a>
            </div>
        </div>
    </IsRole>
</div>

<style>
    /* ---------- Hero / branding ---------- */
    .dash-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        padding: 1.5rem 1.5rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #3d5afe 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.15);
    }

    .dash-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .dash-hero__logo {
        width: 4.5rem;
        height: 4.5rem;
        object-fit: contain;
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.4rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.25);
        flex-shrink: 0;
    }

    .dash-hero__text {
        min-width: 0;
    }

    .dash-hero__eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        opacity: 0.85;
    }

    .dash-hero__title {
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0.1rem 0 0.15rem;
        line-height: 1.15;
        color: #fff;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }

    .dash-hero__subtitle {
        margin: 0;
        opacity: 0.92;
        font-size: 0.9rem;
    }

    /* ---------- Kutipan ---------- */
    .dash-quote {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        padding: 1.15rem 1.3rem;
        border-radius: 0.9rem;
        color: #fff;
        background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
        box-shadow: 0 0.2rem 0.5rem rgba(107, 78, 255, 0.15);
    }

    .dash-quote__bg {
        position: absolute;
        right: -1.5rem;
        bottom: -2.5rem;
        width: 9rem;
        height: 9rem;
        color: #fff;
        pointer-events: none;
    }

    .dash-quote__icon {
        font-size: 2rem;
        line-height: 1;
        color: rgba(255, 255, 255, 0.85);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .dash-quote__body {
        position: relative;
        z-index: 1;
        min-width: 0;
        padding-top: 0.1rem;
    }

    .dash-quote__teks {
        margin: 0 0 0.3rem;
        font-style: italic;
        font-size: 1.02rem;
        color: #fff;
    }

    .dash-quote__penulis {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
    }

    /* ---------- Stat cards (warna solid & jelas) ---------- */
    .stat-card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.9rem 1rem;
        border-radius: 0.9rem;
        color: #fff;
        box-shadow: 0 0.35rem 0.9rem rgba(0, 0, 0, 0.12);
    }

    .stat-card--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .stat-card--info {
        background: linear-gradient(135deg, #0284c7, #0369a1);
    }
    .stat-card--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .stat-card--warning {
        background: linear-gradient(135deg, #ea580c, #c2410c);
    }

    .stat-icon {
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

    .stat-body {
        min-width: 0;
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
    }

    .stat-label {
        font-size: 0.76rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
    }

    /* ---------- Judul section ---------- */
    .section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-body-emphasis, var(--bs-body-color));
    }

    /* ---------- Avatar mapel (warna solid) ---------- */
    .dash-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.8rem;
        font-weight: 700;
        font-size: 1.05rem;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 0.25rem 0.6rem rgba(0, 0, 0, 0.14);
    }

    .dash-avatar--primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    .dash-avatar--secondary {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    .dash-avatar--success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .dash-avatar--info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
    }
    .dash-avatar--warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    .dash-avatar--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    /* ---------- Role guru ---------- */
    .guru-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1.4rem 1.5rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #6a3dfe 100%);
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.15);
    }

    .guru-panel__body {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .guru-panel__icon {
        font-size: 2.4rem;
        line-height: 1;
        color: rgba(255, 255, 255, 0.9);
        flex-shrink: 0;
    }

    .guru-panel__title {
        margin: 0 0 0.15rem;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
    }

    .guru-panel__subtitle {
        margin: 0;
        font-size: 0.88rem;
        opacity: 0.92;
    }

    /* ---------- Item list (tugas / materi) ---------- */
    .task-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.7rem 0.8rem;
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.7rem;
        background: var(--bs-tertiary-bg, var(--bs-body-bg));
        transition:
            background-color 0.15s ease,
            border-color 0.15s ease,
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .task-item:hover {
        background: var(--bs-secondary-bg, var(--bs-body-bg));
        border-color: var(--bs-primary-border-subtle);
        transform: translateY(-1px);
        box-shadow: 0 0.3rem 0.7rem rgba(0, 0, 0, 0.08);
        color: var(--bs-body-color);
    }

    .task-item__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 0.65rem;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 0.2rem 0.5rem rgba(245, 158, 11, 0.35);
    }

    .task-item--materi .task-item__icon {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 0.2rem 0.5rem rgba(37, 99, 235, 0.35);
    }

    .task-item__body {
        min-width: 0;
        flex-grow: 1;
    }

    .task-item__title {
        font-weight: 600;
        font-size: 0.92rem;
        color: var(--bs-body-emphasis, var(--bs-body-color));
    }

    .task-item__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.2rem;
        font-size: 0.76rem;
        color: var(--bs-secondary-color);
    }

    .task-item__matpel {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        font-weight: 600;
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary-700, var(--bs-body-color));
    }

    .task-item__deadline,
    .task-item__date {
        display: inline-flex;
        align-items: center;
    }

    .task-item--materi .task-item__matpel {
        background: var(--bs-info-bg-subtle);
        color: var(--bs-primary-700, var(--bs-body-color));
    }

    .task-item__arrow {
        color: var(--bs-secondary-color);
        opacity: 0;
        transform: translateX(-4px);
        transition:
            opacity 0.15s ease,
            transform 0.15s ease;
        flex-shrink: 0;
    }

    .task-item:hover .task-item__arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .task-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.2rem 0.5rem;
        color: var(--bs-success);
        font-size: 0.88rem;
        font-weight: 500;
        background: var(--bs-success-bg-subtle);
        border: 1px dashed var(--bs-success-border-subtle);
        border-radius: 0.7rem;
    }

    @media (max-width: 575.98px) {
        .dash-hero {
            padding: 1.1rem 1.1rem;
        }
        .dash-hero__logo {
            width: 3.6rem;
            height: 3.6rem;
        }
        .dash-hero__title {
            font-size: 1.25rem;
            white-space: normal;
        }
        .stat-value {
            font-size: 1.3rem;
        }
        .stat-icon {
            width: 2.4rem;
            height: 2.4rem;
            font-size: 1.05rem;
        }
    }
</style>
