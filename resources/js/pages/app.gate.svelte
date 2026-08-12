<script lang="ts">
    import LearningModuleController from '@/actions/App/Http/Controllers/Module/Learning/LearningModuleController';
    import ModulUjianController from '@/actions/App/Http/Controllers/Module/Ujian/ModulUjianController';
    import { Link, router, usePage, WhenVisible } from '@inertiajs/svelte';
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Col,
        Container,
        Progress,
        Row,
        Spinner,
    } from '@sveltestrap/sveltestrap';
    const page = usePage();
    const authUser = $derived((page.props.auth as any)?.user ?? null);

    const avatar = (name: string) =>
        `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=fdd406&color=006fa5`;

    let user = $derived({
        nama_lengkap: authUser?.name ?? 'Dadan Hidayat',
        nisn: authUser?.nisn ?? '0051234567',
        kelas: authUser?.kelas ?? 'XII RPL 1',
        foto_profil:
            authUser?.foto_profil ?? avatar(authUser?.name ?? 'Dadan Hidayat'),
    });

    let greeting = $derived.by(() => {
        const h = new Date().getHours();
        if (h < 11) return 'Selamat Pagi';
        if (h < 15) return 'Selamat Siang';
        if (h < 18) return 'Selamat Sore';
        return 'Selamat Malam';
    });
    let modules = $state([
        {
            id: 'cbt',
            title: 'Ujian (CBT)',
            description:
                'Akses ujian berbasis komputer, tryout, dan evaluasi harian.',
            icon: 'bi-laptop',
            theme: 'primary',
            bg: 'bg-primary',
            text: 'text-white',
            link: ModulUjianController().url,
            isActive: true,
        },
        {
            id: 'lms',
            title: 'Ruang Belajar (LMS)',
            description:
                'Materi pelajaran, tugas kelas, dan ruang diskusi virtual.',
            icon: 'bi-book-half',
            theme: 'success',
            bg: 'bg-success',
            text: 'text-white',
            link: LearningModuleController().url,
            isActive: true,
        },
        {
            id: 'pengumuman',
            title: 'Mading Digital',
            description:
                'Informasi terbaru, jadwal kegiatan, dan pengumuman sekolah.',
            icon: 'bi-megaphone-fill',
            theme: 'warning',
            bg: 'bg-warning',
            text: 'text-dark',
            link: '#',
            isActive: false,
        },
        {
            id: 'raport',
            title: 'E-Raport',
            description: 'Rekap nilai, absensi harian, dan catatan wali kelas.',
            icon: 'bi-award-fill',
            theme: 'info',
            bg: 'bg-info',
            text: 'text-white',
            link: '#',
            isActive: false,
        },
    ]);

    let loggingOut = $state(false);
    function logout() {
        if (loggingOut) return;
        loggingOut = true;
        router.post(
            '/logout',
            {},
            {
                onFinish: () => (loggingOut = false),
            },
        );
    }
</script>

<div class="gate min-vh-100">
    <header class="gate-hero position-relative overflow-hidden">
        <div class="gate-hero__glow" aria-hidden="true"></div>

        <nav
            class="navbar navbar-expand-lg navbar-dark bg-transparent px-3 py-4 position-relative z-2"
        >
            <Container class="px-0">
                <a
                    class="navbar-brand d-flex align-items-center gap-3"
                    href="/"
                >
                    <span
                        class="logo-wrapper rounded-3 bg-white p-1 shadow-sm d-inline-flex"
                    >
                        <img
                            src="https://smkifsu.sch.id/assets/img/logo.png"
                            alt="Logo"
                            width="40"
                            height="40"
                            class="object-fit-contain"
                        />
                    </span>
                    <span class="text-start lh-1">
                        <span
                            class="d-block fw-bold fs-5 text-white tracking-wide"
                            >PORTAL AKADEMIK</span
                        >
                        <span class="d-block small text-white-50"
                            >SMK Terpadu Informatika</span
                        >
                    </span>
                </a>

                <Button
                    color="outline-light"
                    class="border-2 ms-2 d-none d-sm-inline-flex align-items-center gap-2 fw-semibold rounded-pill px-4 btn-logout"
                    onclick={logout}
                    disabled={loggingOut}
                >
                    {loggingOut ? 'Keluar…' : 'Keluar'}
                    <i class="bi bi-box-arrow-right"></i>
                </Button>
            </Container>
        </nav>

        <Container class="gate-hero__body position-relative z-2 pb-5 mb-4">
            <Row class="align-items-center g-4">
                <Col md={7} lg={8}>
                    <Badge
                        color="light"
                        class="text-primary fw-bold px-3 py-2 rounded-pill mb-3 shadow-sm"
                    >
                        <i class="bi bi-calendar-check-fill me-1"></i> TA 2026/2027
                    </Badge>
                    <h1 class="display-6 fw-bold text-white mb-2">
                        {greeting}, <br class="d-md-none" />
                        <span class="text-warning">{user.nama_lengkap}</span>!
                    </h1>
                    <p class="fs-6 text-white-50 mb-0 max-w-md">
                        Siap belajar hari ini? Pilih modul di bawah untuk
                        memulai aktivitas akademikmu.
                    </p>
                </Col>
                <Col md={5} lg={4}>
                    <div class="profile-wrapper">
                        <WhenVisible data={'user'}>
                            {#snippet fallback()}
                                <div
                                    class="profile-card-skeleton d-flex align-items-center p-3 rounded-4 shadow-sm bg-light placeholder-glow"
                                >
                                    <div
                                        class="rounded-circle bg-secondary placeholder"
                                        style="width: 60px; height: 60px;aspect-ratio: 1/1;"
                                    ></div>
                                    <div class="ms-3 w-100 d-flex flex-column">
                                        <div
                                            class="placeholder col-7 mb-2 rounded"
                                        ></div>
                                        <div
                                            class="placeholder col-4 rounded"
                                        ></div>
                                    </div>
                                </div>
                            {/snippet}
                            {#if user}
                                <div
                                    class="profile-card d-flex align-items-center p-3 rounded-4 shadow-lg text-white"
                                >
                                    <!-- Foto Profil dengan Fallback Image jika kosong -->
                                    <img
                                        src={user.foto_profil ||
                                            'https://via.placeholder.com/60'}
                                        alt={`Foto profil ${user.nisn || 'Siswa'}`}
                                        width="60"
                                        height="60"
                                        class="rounded-circle shadow-sm border border-2 border-white object-fit-cover"
                                    />

                                    <div class="ms-3">
                                        <div
                                            class="fw-bold fs-6 lh-1 mb-1 text-truncate"
                                        >
                                            {user.nisn ?? 'NISN Tidak Tersedia'}
                                        </div>
                                        <div
                                            class="small fw-semibold text-warning d-inline-block px-2 py-1 bg-white bg-opacity-25 rounded-2 mt-1"
                                        >
                                            Kelas {user.kelas ?? '-'}
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        </WhenVisible>
                    </div>
                </Col>
            </Row>
        </Container>
    </header>

    <main class="gate-modules">
        <Container class="max-w-7xl px-4 pb-5">
            <Row class="g-4">
                {#each modules as mod (mod.id)}
                    <Col md={6}>
                        {#if mod.isActive}
                            <Link
                                href={mod.link}
                                class="module-card-wrapper text-decoration-none d-block h-100"
                            >
                                <Card
                                    class="h-100 border-0 shadow-sm module-card active-card"
                                >
                                    <CardBody
                                        class="p-4 d-flex align-items-center gap-4"
                                    >
                                        <span
                                            class="icon-badge {mod.bg} {mod.text} d-flex align-items-center justify-content-center flex-shrink-0"
                                        >
                                            <i class="bi {mod.icon} fs-2"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div
                                                class="d-flex justify-content-between align-items-center mb-1"
                                            >
                                                <h3
                                                    class="h5 fw-bold text-dark mb-0"
                                                >
                                                    {mod.title}
                                                </h3>
                                                {#if mod.id === 'cbt'}
                                                    <Badge
                                                        color="success"
                                                        class="text-success border border-success"
                                                        >Baru</Badge
                                                    >
                                                {/if}
                                            </div>
                                            <p
                                                class="text-muted small mb-2 line-clamp-2 lh-base"
                                            >
                                                {mod.description}
                                            </p>
                                            <div
                                                class="d-flex align-items-center gap-2 go-link"
                                            >
                                                <span
                                                    class="small fw-bold text-primary"
                                                    >Akses Modul</span
                                                >
                                                <i
                                                    class="bi bi-arrow-right text-primary"
                                                ></i>
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>
                            </Link>
                        {:else}
                            <div
                                class="module-card-wrapper d-block h-100"
                                aria-disabled="true"
                            >
                                <Card
                                    class="h-100 border-0 shadow-sm module-card inactive-card"
                                >
                                    <CardBody
                                        class="p-4 d-flex align-items-center gap-4"
                                    >
                                        <span
                                            class="icon-badge inactive-badge d-flex align-items-center justify-content-center flex-shrink-0"
                                        >
                                            <i class="bi {mod.icon} fs-2"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div
                                                class="d-flex justify-content-between align-items-center mb-1"
                                            >
                                                <h3
                                                    class="h5 fw-bold text-dark mb-0"
                                                >
                                                    {mod.title}
                                                </h3>
                                                <Badge
                                                    color="light"
                                                    class="text-muted border"
                                                    >Segera</Badge
                                                >
                                            </div>
                                            <p
                                                class="text-muted small mb-2 line-clamp-2 lh-base"
                                            >
                                                {mod.description}
                                            </p>
                                            <div
                                                class="d-flex align-items-center gap-2"
                                            >
                                                <span
                                                    class="small fw-bold text-muted"
                                                    >Akses Terkunci</span
                                                >
                                                <i
                                                    class="bi bi-lock-fill text-muted"
                                                ></i>
                                            </div>
                                        </div>
                                    </CardBody>
                                </Card>
                            </div>
                        {/if}
                    </Col>
                {/each}
            </Row>

            <p class="text-center text-muted small mt-5 mb-0">
                Butuh bantuan? Hubungi operator sekolah Anda.
            </p>
        </Container>
    </main>
</div>

<style>
    /* Utility */
    .max-w-7xl {
        max-width: 1200px;
    }
    .max-w-md {
        max-width: 500px;
    }
    .tracking-wide {
        letter-spacing: 0.05em;
    }
    .gate {
        background-color: #f6f8fb;
    }
    .gate-hero {
        background: linear-gradient(135deg, #0091d4 0%, #006fa5 100%);
        border-bottom-left-radius: 2.5rem;
        border-bottom-right-radius: 2.5rem;
    }
    .gate-hero__glow {
        position: absolute;
        top: -40%;
        right: -10%;
        width: 480px;
        height: 480px;
        background: radial-gradient(
            circle,
            rgba(253, 212, 6, 0.18) 0%,
            rgba(253, 212, 6, 0) 70%
        );
        pointer-events: none;
    }

    .logo-wrapper {
        line-height: 0;
    }
    .profile-card {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .gate-modules {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }
    :global(.module-card) {
        border-radius: 1.25rem;
        transition:
            transform 0.25s ease,
            box-shadow 0.25s ease,
            border-color 0.25s ease;
        background: #ffffff;
    }

    .active-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1) !important;
    }

    .active-card:focus-visible {
        outline: 3px solid rgba(0, 145, 212, 0.35);
        outline-offset: 2px;
    }

    .inactive-card {
        opacity: 0.85;
        background-color: #f1f5f9;
        cursor: not-allowed;
    }

    .inactive-badge {
        background-color: #e2e8f0 !important;
        color: #94a3b8 !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Ikon badge — sudut lebar, bayangan dalam halus */
    .icon-badge {
        width: 76px;
        height: 76px;
        border-radius: 1.2rem;
        box-shadow: inset 0 -4px 0 rgba(0, 0, 0, 0.12);
        transition:
            transform 0.3s ease,
            border-radius 0.3s ease;
    }

    .active-card:hover .icon-badge {
        transform: scale(1.06);
        border-radius: 50%;
    }

    /* Affordance pada tautan akses */
    .go-link i {
        transition: transform 0.25s ease;
    }
    .active-card:hover .go-link i {
        transform: translateX(6px);
    }

    /* Logout — invert ke putih dengan teks brand saat hover */
    :global(.btn-logout:hover),
    :global(.btn-logout:focus-visible) {
        background-color: #ffffff !important;
        color: #006fa5 !important;
        border-color: #ffffff !important;
    }
</style>
