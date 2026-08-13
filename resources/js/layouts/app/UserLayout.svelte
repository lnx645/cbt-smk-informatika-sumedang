<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavSection,
        type AppShellNavItem,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';
    import MataPelajaranGuruController from '@/actions/App/Http/Controllers/MataPelajaranGuruController';
    import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
    let { children }: { children: Snippet } = $props();
    const authUser = $derived((usePage().props.auth as any)?.user ?? null);
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
                section: 'app',
                items: [
                    {
                        href: MataPelajaranGuruController.index().url,
                        label: 'Diskusi',
                        icon: 'bi-chat-dots',
                    },
                ],
            } satisfies AppShellNavSection as any);
            items.push({
                section: 'Akademik',
                items: [
                    {
                        href: '/guru/materi',
                        label: 'Materi',
                        icon: 'bi-journal-plus',
                    },
                    {
                        href: '/guru/tugas-siswa',
                        label: 'Tugas',
                        icon: 'bi-clipboard-check',
                    },
                    {
                        href: '/guru/tugas-ujian',
                        label: 'Soal Ujian',
                        icon: 'bi-journal-text',
                    },
                    {
                        href: '/nilai',
                        label: 'Nilai Siswa',
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
                    label: 'Akademik Siswa',
                    icon: 'bi-mortarboard',
                    children: [
                        {
                            href: '/materi',
                            label: 'Lihat Materi',
                            icon: 'bi-book-half',
                        },
                        {
                            href: '/tugas',
                            label: 'Kerjakan Tugas',
                            icon: 'bi-file-earmark-text',
                        },
                        {
                            href: '/ujian',
                            label: 'Ikuti Ujian',
                            icon: 'bi-ui-checks-grid',
                        },
                    ],
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
    title="Modul Pembelajaran"
    description="Sistem Ujian dan Materi Interaktif"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
