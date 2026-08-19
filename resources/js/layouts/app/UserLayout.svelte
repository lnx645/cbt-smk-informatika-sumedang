<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavSection,
        type AppShellNavItem,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';
    import { Alert } from '@sveltestrap/sveltestrap';
    import MataPelajaranGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import GuruMateriController from '@/actions/App/Http/Controllers/Guru/MateriController';
    import GuruPenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';
    import GuruTugasController from '@/actions/App/Http/Controllers/Guru/TugasController';
    import SiswaMateriController from '@/actions/App/Http/Controllers/Siswa/MateriController';
    import SiswaTugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';
    import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
    let { children }: { children: Snippet } = $props();
    const authUser = $derived(
        (usePage().props.auth as any)?.user ?? null,
    );
    const tahunAjaranAktif = $derived(
        (usePage().props as any).tahunAjaranAktif ?? null,
    );
    const showTahunAjaranInfo = $derived(
        !!authUser?.siswa && !tahunAjaranAktif,
    );
    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Pengguna',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.guru
            ? 'Guru Pengajar'
            : authUser?.siswa
              ? `Siswa ${authUser.siswa.kelas?.nama ?? ''}`
              : 'Pengguna Aktif',
        homeHref: '/',
    });
    const navItems = $derived.by<AppShellNavItem[]>(() => {
        if (!authUser?.gate_access) {
            return [
                {
                    href: '',
                    label: 'Dashboard',
                    icon: 'bi-grid-1x2',
                },
            ];
        }

        const items: AppShellNavItem[] = [
            {
                href: DashboardController().url,
                label: 'Dashboard',
                icon: 'bi-speedometer2',
            },
        ];

        if (authUser?.guru || authUser?.role === 'guru') {
            items.push({
                section: 'Akademik',
                items: [
                    {
                        href: MataPelajaranGuruController.index().url,
                        label: 'Kelas Saya',
                        icon: 'bi-chat-dots',
                    },
                    {
                        href: GuruMateriController.index().url,
                        label: 'Materi',
                        icon: 'bi-journal-plus',
                    },
                    {
                        href: GuruTugasController.index().url,
                        label: 'Tugas',
                        icon: 'bi-clipboard-check',
                    },

                    {
                        href: GuruPenilaianController.index().url,
                        label: 'Penilaian',
                        icon: 'bi-award',
                    },
                ],
            } satisfies AppShellNavSection as any);
        }

        if (authUser?.siswa || authUser?.role === 'siswa') {
            items.push(
                {
                    href: MataPelajaranGuruController.index().url,
                    label: 'Mata Pelajaran',
                    icon: 'bi-journal-bookmark',
                },
                {
                    href: SiswaMateriController.index().url,
                    label: 'Lihat Materi',
                    icon: 'bi-book-half',
                },

                {
                    href: SiswaTugasController.index().url,
                    label: 'Tugas',
                    icon: 'bi-ui-checks-grid',
                },
            );
        }

        return items;
    });
</script>

{#snippet brandIconNode()}
    <BrandIcon />
{/snippet}

<AppShellLayout
    brandTitle="Pembelajaran"
    brandSubtitle="Modul Pembelajaran"
    {brandIconNode}
    title="GURU"
    description="Manajemen Guru"
    {navItems}
    {user}
>
    {#if showTahunAjaranInfo}
        <Alert
            color="info"
            class="d-flex align-items-center gap-2 mb-3"
        >
            <i class="bi bi-info-circle-fill"></i>
            <span>Tahun Pelajaran Baru Belum dimulai</span>
        </Alert>
    {/if}
    {@render children?.()}
</AppShellLayout>
