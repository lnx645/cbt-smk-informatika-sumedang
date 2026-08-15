<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavItem,
        type AppShellNavSection,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';
    import DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
    import JurusanController from '@/actions/App/Http/Controllers/Admin/JurusanController';
    import MatpelController from '@/actions/App/Http/Controllers/Admin/MatpelController';
    import TahunAjaranController from '@/actions/App/Http/Controllers/Admin/TahunAjaranController';
    import KelasController from '@/actions/App/Http/Controllers/Admin/KelasController';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import JamPelajaranController from '@/actions/App/Http/Controllers/Admin/JamPelajaranController';

    let { children }: { children: Snippet } = $props();

    const authUser = $derived((usePage().props.auth as any)?.user ?? null);

    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Administrator',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.role ?? 'Administrator',
        homeHref: DashboardController().url,
    });

    const navItems: Array<AppShellNavItem | AppShellNavSection> = [
        {
            href: DashboardController().url,
            label: 'Dashboard',
            icon: 'bi-speedometer2',
        },
        {
            section: 'Data Master',
            items: [
                {
                    href: PengajarController.index().url,
                    label: 'Pengajar',
                    icon: 'bi-people-fill',
                    badge: '482',
                },
                {
                    href: '/manage/modul',
                    label: 'Peserta Didik',
                    icon: 'bi-mortarboard-fill',
                    badge: '24',
                },
                {
                    href: '/manage/ujian',
                    label: 'Ujian',
                    icon: 'bi-clipboard-check',
                    badge: '3',
                },
                {
                    href: '/manage/bank-soal',
                    label: 'Bank Soal',
                    icon: 'bi-question-circle-fill',
                    badge: '120',
                },
                {
                    href: KelasController.index().url,
                    label: 'Kelas',
                    icon: 'bi-collection-fill',
                },
            ],
        },

        {
            section: 'Account',
            items: [
                {
                    href: '/manage/profile',
                    label: 'Profil',
                    icon: 'bi-person-circle',
                },
            ],
        },
        {
            href: '#',
            label: 'Pengaturan',
            icon: 'bi-gear-fill',
            children: [
                {
                    label: 'Pengaturan Jurusan',
                    href: JurusanController.index().url,
                    icon: 'bi-diagram-3-fill',
                },
                {
                    label: 'Pengaturan Matpel',
                    href: MatpelController.index().url,
                    icon: 'bi-book-half',
                },
                {
                    label: 'Pengaturan Tahun Ajaran',
                    href: TahunAjaranController.index().url,
                    icon: 'bi-calendar',
                },
                {
                    label: 'Pengaturan Jam Pelajaran',
                    href: JamPelajaranController.index().url,
                    icon: 'bi-clock',
                },
            ],
        },
    ];
</script>

{#snippet brandIconNode()}
    <BrandIcon />
{/snippet}

<AppShellLayout
    brandTitle="ADMIN"
    brandSubtitle="Panel Manajemen"
    {brandIconNode}
    title="Panel Administrasi"
    description="Kelola sistem ujian dan pembelajaran"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
