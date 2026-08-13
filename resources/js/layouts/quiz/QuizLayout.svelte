<script lang="ts">
    import type { Snippet } from 'svelte';
    import { usePage } from '@inertiajs/svelte';
    import AppShellLayout, {
        type AppShellNavItem,
        type AppShellUser,
    } from '@/layouts/AppShellLayout.svelte';
    import BrandIcon from './BrandIcon.svelte';

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
            label: 'Belajar',
            icon: 'bi-journal-richtext',
            children: [
                {
                    href: '/modul',
                    label: 'Modul Belajar',
                    icon: 'bi-journal-text',
                },
                {
                    href: '/ujian',
                    label: 'Ujian & Kuis',
                    icon: 'bi-file-earmark-text-fill',
                    badge: 'Baru',
                },
            ],
        },
    ];
</script>

{#snippet brandIconNode()}
    <BrandIcon />
{/snippet}

<AppShellLayout
    brandTitle="GATEWAY"
    brandSubtitle="Portal Ujian"
    brandIconNode={brandIconNode}
    title="Ujian & Kuis"
    description="Ikuti ujian dan kerjakan soal"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
