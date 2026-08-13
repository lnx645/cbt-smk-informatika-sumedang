<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavItem,
        type AppShellNavSection,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';

    let { children }: { children: Snippet } = $props();

    const authUser = $derived((usePage().props.auth as any)?.user ?? null);

    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Administrator',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.role ?? 'Administrator',
        homeHref: '/manage',
    });

    const navItems: Array<AppShellNavItem | AppShellNavSection> = [
        { href: '/manage', label: 'Dashboard', icon: 'bi-grid-1x2-fill' },
        {
            section: 'Data Master',
            items: [
                {
                    href: '/manage/users',
                    label: 'Pengguna',
                    icon: 'bi-people-fill',
                    badge: '482',
                },
                {
                    href: '/manage/modul',
                    label: 'Modul',
                    icon: 'bi-journal-richtext',
                    badge: '24',
                },
                {
                    href: '/manage/ujian',
                    label: 'Ujian',
                    icon: 'bi-file-earmark-text-fill',
                    badge: '3',
                },
                {
                    href: '/manage/bank-soal',
                    label: 'Bank Soal',
                    icon: 'bi-clipboard-data',
                    badge: '120',
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
                {
                    href: '/manage/settings',
                    label: 'Pengaturan',
                    icon: 'bi-gear-fill',
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
    brandIconNode={brandIconNode}
    title="Panel Administrasi"
    description="Kelola sistem ujian dan pembelajaran"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
