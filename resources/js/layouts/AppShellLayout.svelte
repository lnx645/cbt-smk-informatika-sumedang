<script lang="ts">
    import { CookieBox } from 'svelte-cookie-consent';
    import {
        Container,
        Collapse,
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

    export type AppShellNavItem = {
        href?: string;
        label: string;
        icon?: string;
        badge?: string;
        children?: AppShellNavItem[];
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
        navItems?: AppShellNavItem[];
        user?: AppShellUser;
        title?: string;
        description?: string;
    } = $props();

    let sidebarOpen = $state(false);

    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
    }

    function isActive(href: string) {
        return page.url.startsWith(href);
    }

    function groupHasActiveChild(item: AppShellNavItem): boolean {
        return (item.children ?? []).some(
            (child) => child.href != null && isActive(child.href),
        );
    }

    let openGroups = $state<Record<string, boolean>>({});

    $effect(() => {
        for (const item of navItems) {
            if (item.children?.length && groupHasActiveChild(item)) {
                openGroups[item.label] = true;
            }
        }
    });

    function toggleGroup(label: string) {
        openGroups[label] = !openGroups[label];
    }

    function logout() {
        router.post(
            '/logout',
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
<CookieConsent/>
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

        <nav class="app-shell__nav">
            {#each navItems as item (item.href ?? item.label)}
                {#if item.children?.length}
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
                    <Collapse
                        isOpen={openGroups[item.label]}
                        class="app-shell__nav-group-collapse"
                    >
                        {#each item.children as child (child.href)}
                            <a
                                use:inertia={{ prefetch: true }}
                                href={child.href}
                                class={`app-shell__nav-item app-shell__nav-subitem ${isActive(child.href ?? '') ? 'active' : ''}`}
                                aria-current={isActive(child.href ?? '')
                                    ? 'page'
                                    : undefined}
                            >
                                <i class="bi {child.icon}"></i>
                                <span>{child.label}</span>
                                {#if child.badge}
                                    <span class="app-shell__nav-badge"
                                        >{child.badge}</span
                                    >
                                {/if}
                            </a>
                        {/each}
                    </Collapse>
                {:else}
                    <a
                        use:inertia={{ prefetch: true }}
                        href={item.href}
                        class={`app-shell__nav-item ${isActive(item.href ?? '') ? 'active' : ''}`}
                        aria-current={isActive(item.href ?? '')
                            ? 'page'
                            : undefined}
                    >
                        <i class="bi {item.icon}"></i>
                        <span>{item.label}</span>
                        {#if item.badge}
                            <span class="app-shell__nav-badge"
                                >{item.badge}</span
                            >
                        {/if}
                    </a>
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
                        <i
                            class={`bi bi-chevron-down app-shell__user-caret ${userMenuOpen ? 'app-shell__user-caret--open' : ''}`}
                        ></i>
                    </DropdownToggle>

                    <DropdownMenu
                        end
                        class={`app-shell__user-menu p-0 overflow-hidden`}
                        style="width: 250px;"
                    >
                        <!-- Bagian Header Dropdown (Biru) -->
                        <div class="app-shell__menu-profile-header">
                            <span class="app-shell__menu-profile-avatar"
                                >{userInitials}</span
                            >
                            <div class="app-shell__menu-profile-text">
                                <div class="app-shell__menu-profile-name">
                                    {user.name}
                                </div>
                                {#if user.role}
                                    <div class="app-shell__menu-profile-role">
                                        {user.role}
                                    </div>
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
                                href={user.homeHref ?? '/'}
                                class="app-shell__custom-dropdown-item"
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
        <main class="app-shell__content">
            <Container fluid class="px-0">
                {@render children?.()}
            </Container>
        </main>
    </div>
</div>
