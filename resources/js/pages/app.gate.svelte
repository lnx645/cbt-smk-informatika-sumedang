<script lang="ts">
    import LearningModuleController from '@/actions/App/Http/Controllers/Module/Learning/LearningModuleController';
    import ModulUjianController from '@/actions/App/Http/Controllers/Module/Ujian/ModulUjianController';
    import { inertia } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import '@/styles/modules/app-gate.scss';

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

    type AuthUser = {
        name?: string;
        email?: string;
        role?: string;
        nisn?: string;
        siswa?: Siswa | null;
    };

    let { auth = {} }: { auth?: { user?: AuthUser | null } } = $props();

    let user = $derived(auth?.user ?? null);
    let siswa = $derived(user?.siswa ?? null);

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

    let profileRows = $derived([
        {
            label: 'NISN',
            value: siswa?.nisn ?? user?.nisn ?? '-',
            icon: 'bi-hash',
        },
        { label: 'NIS', value: siswa?.nis ?? '-', icon: 'bi-upc' },
        { label: 'Kelas', value: siswa?.kelas ?? '-', icon: 'bi-people-fill' },
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
    ]);

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
    {navItems}
    user={user as AppShellUser | undefined}
    title="Beranda"
    description="Selamat datang di portal modul pembelajaran dan ujian"
>
    <div class="welcome">
        <!-- Banner Sambutan -->
        <section class="welcome__hero">
            <div class="welcome__hero-text">
                <span class="welcome__eyebrow">Portal Siswa</span>
                <h1 class="welcome__title">
                    {greeting}, {siswa?.nama_lengkap ?? user?.name ?? 'Siswa'} 👋
                </h1>
                <p class="welcome__subtitle">
                    {siswa
                        ? `Kelas ${siswa.kelas ?? '-'}${siswa.jurusan ? ' - ' + siswa.jurusan : ''}`
                        : 'Lengkapi data diri Anda untuk pengalaman belajar yang lebih baik.'}
                </p>
            </div>
            <div class="welcome__hero-badge">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
        </section>

        <div class="welcome__grid">
            <!-- Kartu Profil Siswa -->
            <section
                class={`welcome__card welcome__card--profile`}
            >
                <header class="welcome__card-head">
                    <i class="bi bi-person-badge-fill"></i>
                    <h2>Profil Siswa</h2>
                </header>
                <dl class="welcome__profile">
                    {#each profileRows as row (row.label)}
                        <div class="welcome__profile-row">
                            <dt>
                                <i class={`bi ${row.icon}`}></i>
                                <span>{row.label}</span>
                            </dt>
                            <dd>{row.value}</dd>
                        </div>
                    {/each}
                </dl>
            </section>

            <!-- Akses Cepat -->
            <section
                class={`welcome__card welcome__card--quick`}
            >
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
                                <i class={`bi ${link.icon}`}></i>
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
    </div>
</AppShellLayout>
