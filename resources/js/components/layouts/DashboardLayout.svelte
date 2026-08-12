<script lang="ts">
    import { Link, usePage } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppHead from '../AppHead.svelte';

    let { children }: { children?: Snippet } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    const page = usePage();

    const titles: Record<string, string> = {
        Dashboard: 'Dashboard',
    };

    const title = $derived(titles[page.component] ?? page.component);
    const onDashboard = $derived(page.url.startsWith('/dashboard'));

    const primaryLinks = [{ label: 'Dashboard', url: '/dashboard' }];

    const secondaryLinks = [
        { label: 'Ujian', url: '' },
        { label: 'Bank Soal', url: '' },
        { label: 'Hasil Ujian', url: '' },
        { label: 'Pengaturan', url: '' },
    ];
</script>

<AppHead />

<div class="d-flex min-vh-100 bg-light-subtle">
    <aside
        class="dashboard-sidebar bg-primary text-white d-none d-md-flex flex-column"
        style="width: 260px; min-height: 100vh; position: sticky; top: 0"
    >
        <div
            class="p-4 pb-3 mb-2"
            style="border-bottom: 1px solid rgba(255, 255, 255, 0.2)"
        >
            <span class="fs-5 fw-bold text-white">{appName}</span>
            <div class="mt-1 d-flex align-items-center gap-2">
                <span class="badge rounded-pill dashboard-sidebar-badge"
                    >Tut Wuri</span
                >
                <span class="small opacity-75">CBT</span>
            </div>
        </div>

        <nav class="px-3 d-flex flex-column gap-1">
            <span class="sidebar-label small text-uppercase px-2 mb-1"
                >Menu Utama</span
            >
            {#each primaryLinks as link (link.label)}
                <Link
                    href={link.url}
                    class="sidebar-link d-flex align-items-center gap-2 px-3 py-2 rounded text-white text-decoration-none{onDashboard
                        ? ' sidebar-link-active'
                        : ''}"
                >
                    {link.label}
                </Link>
            {/each}

            <span class="sidebar-label small text-uppercase px-2 mt-3 mb-1"
                >Lainnya</span
            >
            {#each secondaryLinks as link (link.label)}
                {#if link.url}
                    <Link
                        href={link.url}
                        class="sidebar-link d-flex align-items-center gap-2 px-3 py-2 rounded text-white opacity-75 text-decoration-none"
                    >
                        {link.label}
                    </Link>
                {:else}
                    <span
                        class="sidebar-link d-flex align-items-center gap-2 px-3 py-2 rounded text-white opacity-50"
                    >
                        {link.label}
                    </span>
                {/if}
            {/each}
        </nav>
    </aside>

    <div class="d-flex flex-column flex-grow-1" style="min-width: 0">
        <header
            class="bg-white border-bottom px-3 px-md-4 py-3 d-flex align-items-center justify-content-between dashboard-topbar"
        >
            <div class="d-flex align-items-center gap-3">
                <span class="d-md-none fw-bold text-dark">{appName}</span>
                <h1 class="h5 mb-0 text-dark">{title}</h1>
            </div>
            <span class="badge rounded-pill dashboard-sidebar-badge text-dark"
                >Admin</span
            >
        </header>

        <main class="p-3 p-md-4">
            {@render children?.()}
        </main>
    </div>
</div>

<style>
    .sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    :global(.sidebar-link-active) {
        background-color: rgba(255, 255, 255, 0.18);
        box-shadow: inset 3px 0 0 #fdd406;
    }

    .sidebar-label {
        letter-spacing: 0.08em;
        opacity: 0.7;
    }

    .dashboard-sidebar-badge {
        background-color: #fdd406;
        color: #006fa5;
    }
</style>
