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
            label: 'Akademik',
            icon: 'bi-journal-richtext',
            children: [
                { href: '/matpel', label: 'Mata Pelajaran', icon: 'bi-journal-bookmark' },
                {
                    href: '/tugas',
                    label: 'Tugas Saya',
                    icon: 'bi-file-earmark-text-fill',
                },
            ],
        },
    ];
</script>

<AppShellLayout
    brandTitle="Pembelajaran"
    brandSubtitle="Modul Pembelajaran Siswa"
    brandIcon="bi-shield-lock-fill"
    title="Modul Pembelajaran"
    description="Sistem Ujian dan Materi Interaktif"
    {navItems}
    {user}
>
    {@render children?.()}
</AppShellLayout>
