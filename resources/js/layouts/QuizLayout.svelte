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
        name: authUser?.name ?? 'Peserta Didik',
        email: authUser?.email ?? '',
        id: authUser?.id ?? '',
        role: authUser?.kelas ? `Siswa ${authUser.kelas}` : 'Siswa Aktif',
        homeHref: '/dashboard',
    });

    const navItems: AppShellNavItem[] = [
        { href: '/dashboard', label: 'Dashboard', icon: 'bi-grid-1x2-fill' },
        {
            href: '/modul',
            label: 'Modul Belajar',
            icon: 'bi-journal-richtext',
        },
        {
            href: '/ujian',
            label: 'Ujian & Kuis',
            icon: 'bi-file-earmark-text-fill',
        },
    ];
</script>

<AppShellLayout
    brandTitle="GATEWAY"
    brandSubtitle="Portal Ujian"
    brandIcon="bi-shield-lock-fill"
    title="Ujian & Kuis"
    description="Ikuti ujian dan kerjakan soal"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
