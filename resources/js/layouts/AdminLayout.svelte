<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavItem,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';

    let { children }: { children: Snippet } = $props();

    const authUser = $derived((usePage().props.auth as any)?.user ?? null);

    const user = $derived<AppShellUser>({
        name: authUser?.name ?? 'Administrator',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.role ?? 'Administrator',
        homeHref: '/manage',
    });

    const navItems: AppShellNavItem[] = [
        { href: '/manage', label: 'Dashboard', icon: 'bi-grid-1x2-fill' },
        {
            label: 'Manajemen',
            icon: 'bi-kanban-fill',
            children: [
                { href: '/manage/users', label: 'Pengguna', icon: 'bi-people-fill' },
                { href: '/manage/modul', label: 'Modul', icon: 'bi-journal-richtext' },
                { href: '/manage/ujian', label: 'Ujian', icon: 'bi-file-earmark-text-fill' },
            ],
        },
        { href: '/manage/settings', label: 'Pengaturan', icon: 'bi-gear-fill' },
    ];
</script>

<AppShellLayout
    brandTitle="ADMIN"
    brandSubtitle="Panel Manajemen"
    brandIcon="bi-sliders"
    title="Panel Administrasi"
    description="Kelola sistem ujian dan pembelajaran"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
