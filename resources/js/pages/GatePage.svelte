<script lang="ts">
    import LearningModuleController from '@/actions/App/Http/Controllers/Module/Learning/LearningModuleController';
    import ModulUjianController from '@/actions/App/Http/Controllers/Module/Ujian/ModulUjianController';
    import { inertia } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import { Alert } from '@sveltestrap/sveltestrap';
    import '@/styles/modules/gate-page.scss';
    import LinkExternalController from '@/actions/App/Http/Controllers/LinkExternalController';
    import { linkExternal } from '@/lib/utils';

    type Siswa = {
        nisn?: string;
        nis?: string;
        nama_lengkap?: string;
        kelas?: string;
        jurusan?: string;
        jenis_kelamin?: string;
        tempat_lahir?: string;
        tanggal_lahir?: string;
    };

    type Guru = {
        nip?: string;
        nama_lengkap?: string;
        jenis_kelamin?: string;
        alamat?: string;
    };

    type AuthUser = {
        name?: string;
        email?: string;
        role?: 'siswa' | 'guru' | 'admin' | string;
        gate_access: boolean;
        nisn?: string;
        siswa?: Siswa | null;
        guru?: Guru | null;
    };

    let { auth = {} }: { auth?: { user?: AuthUser | null } } = $props();

    let user = $derived(auth?.user ?? null);
    let siswa = $derived(user?.siswa ?? null);
    let guru = $derived(user?.guru ?? null);

    // Deteksi role aktif saat ini secara otomatis
    let currentRole = $derived(
        user?.role ?? (guru ? 'guru' : siswa ? 'siswa' : 'pengguna'),
    );

    let greeting = $derived.by(() => {
        const h = new Date().getHours();
        if (h < 11) return 'Selamat pagi';
        if (h < 15) return 'Selamat siang';
        if (h < 19) return 'Selamat sore';
        return 'Selamat malam';
    });

    let navItems = [
        {
            label: 'Pembelajaran',
            icon: 'bi-journal-richtext',
            href: LearningModuleController().url,
        },
        {
            label: 'Ujian (CBT)',
            icon: 'bi-file-earmark-text-fill',
            href: ModulUjianController().url,
        },
    ];

    let quickLinks = [
        {
            label: 'Modul Pembelajaran',
            desc: 'Akses materi dan modul belajar daring.',
            icon: 'bi-journal-bookmark-fill',
            href: LearningModuleController().url,
        },
        {
            label: 'Ujian CBT',
            desc: 'Ikuti ujian berbasis komputer dengan aman.',
            icon: 'bi-clipboard2-check-fill',
            href: ModulUjianController().url,
        },
    ];

    // Profile rows otomatis berubah tergantung apakah yang login Siswa atau Guru
    let profileRows = $derived.by(() => {
        if (guru) {
            return [
                { label: 'NIP', value: guru.nip ?? '-', icon: 'bi-hash' },
                {
                    label: 'Jenis Kelamin',
                    value:
                        guru.jenis_kelamin === 'L'
                            ? 'Laki-laki'
                            : guru.jenis_kelamin === 'P'
                              ? 'Perempuan'
                              : '-',
                    icon: 'bi-gender-ambiguous',
                },
                {
                    label: 'Alamat',
                    value: guru.alamat ?? '-',
                    icon: 'bi-geo-alt-fill',
                },
            ];
        }

        return [
            {
                label: 'NISN',
                value: siswa?.nisn ?? user?.nisn ?? '-',
                icon: 'bi-hash',
            },
            { label: 'NIS', value: siswa?.nis ?? '-', icon: 'bi-upc' },
            {
                label: 'Kelas',
                value: siswa?.kelas ?? '-',
                icon: 'bi-people-fill',
            },
            {
                label: 'Jurusan',
                value: siswa?.jurusan ?? '-',
                icon: 'bi-bookmark-star-fill',
            },
            {
                label: 'Jenis Kelamin',
                value:
                    siswa?.jenis_kelamin === 'L'
                        ? 'Laki-laki'
                        : siswa?.jenis_kelamin === 'P'
                          ? 'Perempuan'
                          : '-',
                icon: 'bi-gender-ambiguous',
            },
            {
                label: 'Tempat, Tgl Lahir',
                value: formatLahir(siswa),
                icon: 'bi-calendar-event',
            },
        ];
    });

    function formatLahir(s: Siswa | null): string {
        if (!s) return '-';
        const tempat = s.tempat_lahir ?? '';
        const tanggal = s.tanggal_lahir
            ? new Date(s.tanggal_lahir).toLocaleDateString('id-ID', {
                  day: 'numeric',
                  month: 'long',
                  year: 'numeric',
              })
            : '';
        const parts = [tempat, tanggal].filter(Boolean);
        return parts.length ? parts.join(', ') : '-';
    }
</script>

<AppShellLayout
    navItems={user?.gate_access
        ? navItems
        : [
              {
                  label: 'Operator Sekolah',
                  icon: 'bi bi-whatsapp',
                  href: linkExternal('?link=http://wa.link/4etqm1'),
              },
              {
                  label: 'Operator Sekolah 2',
                  icon: 'bi bi-whatsapp',
                  href: linkExternal('?link=http://wa.link/4etqm2'),
              },
          ]}
    user={user as AppShellUser | undefined}
    title="Beranda"
    description="Selamat datang di portal modul pembelajaran dan ujian"
>
    <div class="welcome">
        {#if user?.gate_access}
            <!-- Banner Sambutan -->
            <section class="welcome__hero">
                <div class="welcome__hero-text">
                    <span class="welcome__eyebrow">Portal {currentRole}</span>
                    <h1 class="welcome__title">
                        {greeting}, {guru?.nama_lengkap ??
                            siswa?.nama_lengkap ??
                            user?.name ??
                            'Pengguna'} 👋
                    </h1>
                    <p class="welcome__subtitle">
                        {#if guru}
                            Panel kontrol pengajar dan manajemen pembelajaran.
                        {:else if siswa}
                            {`Kelas ${siswa.kelas ?? '-'}${siswa.jurusan ? ' - ' + siswa.jurusan : ''}`}
                        {:else}
                            Lengkapi data diri Anda untuk pengalaman belajar
                            yang lebih baik.
                        {/if}
                    </p>
                </div>
                <div class="welcome__hero-badge">
                    <i
                        class="bi {guru
                            ? 'bi-person-workspace'
                            : 'bi-mortarboard-fill'}"
                    ></i>
                </div>
            </section>

            <div class="welcome__grid">
                <!-- Kartu Profil Dinamis (Siswa / Guru) -->
                <section class={`welcome__card welcome__card--profile`}>
                    <header class="welcome__card-head">
                        <i
                            class="bi {guru
                                ? 'bi-person-badge'
                                : 'bi-person-badge-fill'}"
                        ></i>
                        <h2>Profil {guru ? 'Guru' : 'Siswa'}</h2>
                    </header>
                    <dl class="welcome__profile">
                        {#each profileRows as row (row.label)}
                            <div class="welcome__profile-row">
                                <dt>
                                    <i class="bi {row.icon}"></i>
                                    <span>{row.label}</span>
                                </dt>
                                <dd>{row.value}</dd>
                            </div>
                        {/each}
                    </dl>
                </section>

                <!-- Akses Cepat -->
                <section class={`welcome__card welcome__card--quick`}>
                    <header class="welcome__card-head">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <h2>Akses Cepat</h2>
                    </header>
                    <div class="welcome__quick-list">
                        {#each quickLinks as link (link.label)}
                            <a
                                class="welcome__quick"
                                href={link.href}
                                use:inertia
                            >
                                <span class="welcome__quick-icon">
                                    <i class="bi {link.icon}"></i>
                                </span>
                                <span class="welcome__quick-body">
                                    <span class="welcome__quick-label"
                                        >{link.label}</span
                                    >
                                    <span class="welcome__quick-desc"
                                        >{link.desc}</span
                                    >
                                </span>
                                <i
                                    class={`bi bi-chevron-right welcome__quick-arrow`}
                                ></i>
                            </a>
                        {/each}
                    </div>
                </section>
            </div>
        {:else}
            <Alert color="warning">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    <div>
                        <h4 class="alert-heading fw-bold mb-1">
                            Akses Terbatas
                        </h4>
                        <p class="mb-2">
                            Akun Anda belum terdaftar sebagai
                            <strong>siswa</strong> maupun
                            <strong>guru</strong>, sehingga belum berhak
                            mengakses fitur di halaman ini.
                        </p>
                        <hr class="my-2" />
                        <p class="mb-0 small">
                            Jika ini merupakan keliruan, silakan hubungi
                            operator sekolah untuk verifikasi data Anda.
                        </p>
                    </div>
                </div>
            </Alert>
        {/if}
    </div>
</AppShellLayout>
