<script lang="ts">
    import { CookieBox } from 'svelte-cookie-consent';
    import {
        Container,
        Dropdown,
        DropdownToggle,
        DropdownMenu,
        DropdownItem,
    } from '@sveltestrap/sveltestrap';
    import type { Snippet } from 'svelte';
    import { Toaster } from 'svelte-sonner';
    import { inertia, page, router } from '@inertiajs/svelte';
    import '@/styles/modules/app-shell-layout.scss';
    import CookieConsent from '@/components/CookieConsent.svelte';
    import AuthenticatedSessionController from '@/actions/App/Http/Controllers/AuthenticatedSessionController';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';
    import TahunAjaranInfo from '@/components/TahunAjaranInfo.svelte';

    export type AppShellNavItem = {
        href?: string;
        label: string;
        icon?: string;
        badge?: string;
        children?: AppShellNavItem[];
    };

    export type AppShellNavSection = {
        section: string;
        icon?: string;
        items: AppShellNavItem[];
    };

    export type AppShellUser = {
        name: string;
        email?: string;
        id?: string | number;
        role: string;
        homeHref?: string;
    };

    let {
        children,
        brandTitle = 'GATEWAY',
        brandSubtitle = 'Portal Modul',
        brandIcon = 'bi-shield-lock-fill',
        brandIconNode,
        navItems = [],
        user = { name: 'Pengguna', email: '', id: '', role: '' },
        title = 'Dashboard',
        description = '',
    }: {
        children: Snippet;
        brandTitle?: string;
        brandSubtitle?: string;
        brandIcon?: string;
        brandIconNode?: Snippet;
        navItems?: Array<AppShellNavItem | AppShellNavSection>;
        user?: AppShellUser;
        title?: string;
        description?: string;
    } = $props();

    let sidebarOpen = $state(false);

    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
    }

    function normalizeUrl(url: string): string {
        return url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/';
    }

    // Only ONE item should be highlighted. Pick the most specific (longest)
    // matching href so a parent path (e.g. /m/pembelajaran) does not also
    // light up when a child (e.g. /m/pembelajaran/mata-pelajaran) is active.
    let activeHref = $derived.by<string | null>(() => {
        const current = normalizeUrl(page.url);
        let best: string | null = null;
        const consider = (href?: string) => {
            if (!href) return;
            const target = normalizeUrl(href);
            if (current === target || current.startsWith(target + '/')) {
                if (
                    best === null ||
                    target.length > normalizeUrl(best).length
                ) {
                    best = href;
                }
            }
        };
        for (const item of navItems) {
            if ('section' in item) {
                for (const child of item.items ?? []) {
                    consider(child.href);
                }
                continue;
            }
            consider(item.href);
            for (const child of item.children ?? []) {
                consider(child.href);
            }
        }
        return best;
    });

    function isActive(href: string) {
        return activeHref === href;
    }

    function groupHasActiveChild(
        item: AppShellNavItem | AppShellNavSection,
    ): boolean {
        if ('section' in item) {
            return false;
        }
        return (item.children ?? []).some(
            (child) => child.href != null && isActive(child.href),
        );
    }

    let openGroups = $state<Record<string, boolean>>({});

    $effect(() => {
        for (const item of navItems) {
            if (!('section' in item) && groupHasActiveChild(item)) {
                openGroups[item.label] = true;
            }
        }
    });

    function toggleGroup(label: string) {
        openGroups[label] = !openGroups[label];
    }

    function logout() {
        router.post(AuthenticatedSessionController.destroy(),
            {},
            {
                onFinish: () => {
                    sidebarOpen = false;
                },
            },
        );
    }

    let userInitials = $derived(
        user.name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part: string) => part.charAt(0).toUpperCase())
            .join(''),
    );

    let userMenuOpen = $state(false);
</script>

<CookieConsent />
<Toaster richColors position="top-right" />

<div class="app-shell">
    <!-- Sidebar -->
    <aside class={`app-shell__sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div class="app-shell__brand">
            <div class="app-shell__brand-badge">
                {#if brandIconNode}
                    {@render brandIconNode()}
                {:else}
                    <i class="bi {brandIcon}"></i>
                {/if}
            </div>
            <div class="app-shell__brand-text">
                <h5 class="app-shell__brand-title">{brandTitle}</h5>
                <span class="app-shell__brand-subtitle">{brandSubtitle}</span>
            </div>
        </div>

        {#snippet navLink(entry: AppShellNavItem, subitem: boolean)}
            <a
                use:inertia={{ prefetch: true }}
                href={entry.href}
                class={`app-shell__nav-item ${subitem ? 'app-shell__nav-subitem' : ''} ${isActive(entry.href ?? '') ? 'active' : ''}`}
                aria-current={isActive(entry.href ?? '') ? 'page' : undefined}
            >
                <i class="bi {entry.icon}"></i>
                <span>{entry.label}</span>
                {#if entry.badge}
                    <span class="app-shell__nav-badge">{entry.badge}</span>
                {/if}
            </a>
        {/snippet}

        <nav class="app-shell__nav">
            {#each navItems as item ('section' in item ? item.section : (item.href ?? item.label))}
                {#if 'section' in item}
                    <div class="app-shell__nav-category">{item.section}</div>
                    {#each item.items as child (child.href ?? child.label)}
                        {@render navLink(child, true)}
                    {/each}
                {:else if item.children?.length}
                    <button
                        type="button"
                        class={`app-shell__nav-item app-shell__nav-group ${openGroups[item.label] ? 'is-open' : ''}`}
                        onclick={() => toggleGroup(item.label)}
                        aria-expanded={openGroups[item.label]}
                    >
                        <i class="bi {item.icon}"></i>
                        <span class="app-shell__nav-group-label"
                            >{item.label}</span
                        >
                        {#if item.badge}
                            <span class="app-shell__nav-badge"
                                >{item.badge}</span
                            >
                        {/if}
                        <i
                            class={`bi bi-chevron-down app-shell__nav-group-caret`}
                        ></i>
                    </button>
                    <div
                        class={`app-shell__nav-group-collapse ${openGroups[item.label] ? 'is-expanded' : ''}`}
                    >
                        <div class="app-shell__nav-group-inner">
                            {#each item.children as child (child.href)}
                                {@render navLink(child, true)}
                            {/each}
                        </div>
                    </div>
                {:else}
                    {@render navLink(item, false)}
                {/if}
            {/each}
        </nav>

        <div class="app-shell__sidebar-footer">
            <button
                type="button"
                class={`app-shell__nav-item app-shell__nav-item--danger`}
                onclick={logout}
            >
                <i class="bi bi-box-arrow-left"></i>
                <span>Keluar Sesi</span>
            </button>
        </div>
    </aside>

    {#if sidebarOpen}
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div class="app-shell__overlay" onclick={toggleSidebar}></div>
    {/if}

    <!-- Main Content Wrapper -->
    <div class="app-shell__main">
        <!-- Header -->
        <header class="app-shell__header">
            <div class="app-shell__header-left">
                <button
                    class="app-shell__menu-toggle"
                    onclick={toggleSidebar}
                    aria-label="Buka menu navigasi"
                >
                    <i class="bi bi-list"></i>
                </button>
                <div class="app-shell__header-title-wrapper">
                    <h1 class="app-shell__header-title">{title}</h1>
                    {#if description}
                        <p class="app-shell__header-desc">
                            {description}
                        </p>
                    {/if}
                </div>
            </div>

            <div class="app-shell__header-right">
                <Dropdown
                    direction="down"
                    class="app-shell__user-dropdown"
                    isOpen={userMenuOpen}
                    toggle={() => (userMenuOpen = !userMenuOpen)}
                >
                    <DropdownToggle
                        nav
                        class={`app-shell__user-toggle d-flex align-items-center gap-2 ${userMenuOpen ? 'is-open' : ''}`}
                        aria-label="Menu pengguna"
                    >
                        <span class="app-shell__user-avatar"
                            >{userInitials}</span
                        >
                        <span class="app-shell__user-meta">
                            <span class="app-shell__user-name">{user.name}</span
                            >
                            {#if user.role}
                                <span
                                    class={`app-shell__user-role app-shell__user-role--${user.role}`}
                                    >{user.role}</span
                                >
                            {/if}
                        </span>
                        <i
                            class={`bi bi-chevron-down app-shell__user-caret ${userMenuOpen ? 'app-shell__user-caret--open' : ''}`}
                        ></i>
                    </DropdownToggle>

                    <DropdownMenu
                        end
                        class={`app-shell__user-menu p-0 overflow-hidden`}
                        style="width: 264px;"
                    >
                        <!-- Bagian Header Dropdown (Biru) -->
                        <div class="app-shell__menu-profile-header">
                            
                            <div class="app-shell__menu-profile-text">
                                <div class="app-shell__menu-profile-name">
                                    {user.name}
                                </div>
                                {#if user.role}
                                    <span class="app-shell__role-badge"
                                        >{user.role}</span
                                    >
                                {/if}
                                {#if user.email}
                                    <div class="app-shell__menu-profile-email">
                                        {user.email}
                                    </div>
                                {/if}
                            </div>
                        </div>

                        <div class="app-shell__menu-links">
                            <a
                                use:inertia
                                href={user.homeHref ?? '/'}
                                class="app-shell__custom-dropdown-item"
                                onclick={() => (userMenuOpen = false)}
                            >
                                <i class="bi bi-house-door"></i>
                                <span>Beranda</span>
                            </a>
                        </div>

                        <DropdownItem divider class="app-shell__menu-divider" />

                        <!-- Tombol Keluar -->
                        <div class="app-shell__menu-footer">
                            <button
                                type="button"
                                class={`app-shell__custom-dropdown-item app-shell__logout-btn`}
                                onclick={logout}
                            >
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Keluar Sesi</span>
                            </button>
                        </div>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </header>

        <!-- Body Content -->
        <main class="app-shell__content p-md-2 p-3 px-2">
            <Container fluid class="px-0">
                {@render children?.()}
            </Container>
        </main>
    </div>
</div>

<ConfirmDialog />
