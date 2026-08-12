<script lang="ts">
    import {
        Container,
        Dropdown,
        DropdownToggle,
        DropdownMenu,
        DropdownItem,
    } from '@sveltestrap/sveltestrap';
    import type { Snippet } from 'svelte';
    import { Toaster } from 'svelte-sonner';
    import { page, router } from '@inertiajs/svelte';

    export type AppShellNavItem = {
        href: string;
        label: string;
        icon: string;
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
        navItems = [],
        user = { name: 'Pengguna', email: '', id: '', role: '' },
        title = 'Dashboard',
        description = '',
    }: {
        children: Snippet;
        brandTitle?: string;
        brandSubtitle?: string;
        brandIcon?: string;
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

<Toaster richColors position="top-right" />

<div class="app-shell">
    <!-- Sidebar -->
    <aside class="app-shell__sidebar {sidebarOpen ? 'open' : ''}">
        <div class="app-shell__brand">
            <div class="app-shell__brand-badge">
                <i class="bi {brandIcon}"></i>
            </div>
            <div class="app-shell__brand-text">
                <h5 class="app-shell__brand-title">{brandTitle}</h5>
                <span class="app-shell__brand-subtitle">{brandSubtitle}</span>
            </div>
        </div>

        <nav class="app-shell__nav">
            <div class="app-shell__nav-category">Navigasi Utama</div>
            {#each navItems as item (item.href)}
                <a
                    href={item.href}
                    class="app-shell__nav-item {isActive(item.href) ? 'active' : ''}"
                    aria-current={isActive(item.href) ? 'page' : undefined}
                >
                    <i class="bi {item.icon}"></i>
                    <span>{item.label}</span>
                </a>
            {/each}
        </nav>

        <div class="app-shell__sidebar-footer">
            <button
                type="button"
                class="app-shell__nav-item app-shell__nav-item--danger"
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
                        <p class="app-shell__header-desc">{description}</p>
                    {/if}
                </div>
            </div>

            <div class="app-shell__header-right">
                <Dropdown
                    direction="down"
                    class="app-shell__user-dropdown "
                    isOpen={userMenuOpen}
                    toggle={() => (userMenuOpen = !userMenuOpen)}
                >
                    <DropdownToggle
                        nav
                        class="app-shell__user-toggle d-flex align-items-center gap-2 {userMenuOpen
                            ? 'is-open'
                            : ''}"
                        aria-label="Menu pengguna"
                    >
                        <span class="app-shell__user-avatar">{userInitials}</span>
                        <i
                            class="bi bi-chevron-down app-shell__user-caret {userMenuOpen
                                ? 'app-shell__user-caret--open'
                                : ''}"
                        ></i>
                    </DropdownToggle>
                    
                    <DropdownMenu end class="app-shell__user-menu p-0 overflow-hidden">
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
                                class="app-shell__custom-dropdown-item app-shell__logout-btn"
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

<style>
    .app-shell {
        --tw-blue: #0091d4;
        --tw-blue-dark: #006fa5;
        --tw-gold: #fdd406;
        --tw-gold-soft: rgba(253, 212, 6, 0.16);

        --app-bg: #f6f8fb;
        --surface: #ffffff;
        --border: #e2e8f0;
        --text: #334155;
        --text-muted: #64748b;

        --primary-soft: #e6f4fb;
        --danger: #c25c52;
        --danger-soft: rgba(194, 92, 82, 0.1);

        --brand-gradient: linear-gradient(
            135deg,
            var(--tw-blue) 0%,
            var(--tw-blue-dark) 100%
        );

        display: flex;
        min-height: 100vh;
        background-color: var(--app-bg);
        color: var(--text);
        font-family: inherit;
        -webkit-font-smoothing: antialiased;
    }

    .app-shell__sidebar {
        width: 280px;
        background-color: var(--surface);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 1050;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-shell__brand {
        position: relative;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 1px solid var(--border);
        background: var(--brand-gradient);
        overflow: hidden;
    }

    .app-shell__brand::after {
        content: '';
        position: absolute;
        top: -60%;
        right: -25%;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, var(--tw-gold-soft) 0%, transparent 70%);
        pointer-events: none;
    }

    .app-shell__brand-badge {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.15rem;
        box-shadow: 0 0 14px rgba(253, 212, 6, 0.25);
    }

    .app-shell__brand-text {
        position: relative;
        line-height: 1.15;
    }

    .app-shell__brand-title {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        color: #ffffff;
    }

    .app-shell__brand-subtitle {
        font-size: 0.72rem;
        color: rgba(255, 255, 255, 0.85);
    }

    .app-shell__nav {
        padding: 1.25rem 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        overflow-y: auto;
    }

    .app-shell__nav-category {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        padding: 0 0.75rem;
        margin: 0.25rem 0 0.6rem;
        font-weight: 600;
    }

    .app-shell__nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.7rem 1rem;
        color: var(--text-muted);
        text-decoration: none;
        background: transparent;
        border: none;
        border-radius: 8px;
        font: inherit;
        font-weight: 500;
        font-size: 0.9rem;
        border-left: 3px solid transparent;
        cursor: pointer;
        text-align: left;
        width: 100%;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .app-shell__nav-item i {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .app-shell__nav-item:hover {
        background-color: var(--primary-soft);
        color: var(--tw-blue-dark);
    }

    .app-shell__nav-item.active {
        background-color: var(--primary-soft);
        color: var(--tw-blue-dark);
        border-left-color: var(--tw-gold);
        font-weight: 600;
    }

    .app-shell__nav-item.active i {
        color: var(--tw-blue);
    }

    .app-shell__sidebar-footer {
        padding: 1rem;
        border-top: 1px solid var(--border);
    }

    .app-shell__nav-item--danger:hover {
        background-color: var(--danger-soft);
        color: var(--danger) !important;
        border-left-color: transparent;
    }

    .app-shell__main {
        flex: 1;
        margin-left: 280px;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background-color: var(--app-bg);
    }

    .app-shell__header {
        position: sticky;
        top: 0;
        z-index: 1040;
        background-color: var(--surface);
        border-bottom: 1px solid var(--border);
        padding: 1rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 0 0 var(--border), 0 6px 18px rgba(15, 23, 42, 0.03);
    }

    .app-shell__header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--brand-gradient);
    }

    .app-shell__header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .app-shell__menu-toggle {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text);
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .app-shell__menu-toggle:hover {
        background-color: var(--primary-soft);
        border-color: var(--tw-blue);
        color: var(--tw-blue-dark);
    }

    .app-shell__header-title {
        font-size: 1.15rem;
        font-weight: 600;
        margin: 0;
        color: var(--text);
    }

    .app-shell__header-desc {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin: 0.1rem 0 0;
    }

    .app-shell__user-dropdown {
        display: inline-flex;
    }

    .app-shell__user-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.35rem;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 50px;
        appearance: none;
        font: inherit;
        color: inherit;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .app-shell__user-toggle:hover {
        background-color: var(--primary-soft);
    }

    .app-shell__user-toggle.is-open {
        background-color: var(--primary-soft);
        border-color: var(--border);
    }

    .app-shell__user-toggle:focus-visible {
        outline: 3px solid rgba(0, 145, 212, 0.35);
        outline-offset: 2px;
    }

    .app-shell__user-caret {
        font-size: 0.7rem;
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .app-shell__user-caret--open {
        transform: rotate(180deg);
    }

    /* ----------------------- Custom Dropdown Styling (Menyerupai Referensi) ----------------------- */
    .app-shell__user-menu {
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
        padding: 0;
        margin-top: 0.6rem;
        overflow: hidden;
        background-color: var(--surface);
        transform-origin: top right;
        animation: app-shell-user-pop 0.16s ease-out;
    }

    @keyframes app-shell-user-pop {
        from {
            opacity: 0;
            transform: translateY(-6px) scale(0.97);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .app-shell__menu-profile-header {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        background: var(--brand-gradient);
        color: #ffffff;
        padding: 1.15rem 1.35rem;
    }

    .app-shell__menu-profile-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        flex-shrink: 0;
    }

    .app-shell__menu-profile-text {
        min-width: 0;
    }

    .app-shell__menu-profile-name {
        font-size: 1.02rem;
        font-weight: 700;
        margin-bottom: 0.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .app-shell__menu-profile-email {
        font-size: 0.82rem;
        opacity: 0.9;
        word-break: break-all;
    }

    .app-shell__menu-profile-role {
        font-size: 0.78rem;
        font-weight: 500;
        opacity: 0.92;
        margin-top: 0.1rem;
    }

    .app-shell__menu-links {
        padding: 0.4rem 0.6rem;
    }

    .app-shell__custom-dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.7rem 0.85rem;
        color: var(--text);
        text-decoration: none;
        border-radius: 9px;
        font-size: 0.9rem;
        font-weight: 500;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .app-shell__custom-dropdown-item i {
        font-size: 1.05rem;
        color: var(--text-muted);
        flex-shrink: 0;
        transition: color 0.15s ease;
    }

    .app-shell__custom-dropdown-item:hover {
        background-color: var(--primary-soft);
        color: var(--tw-blue-dark);
    }

    .app-shell__custom-dropdown-item:hover i {
        color: var(--tw-blue);
    }

    .app-shell__menu-divider {
        margin: 0 !important;
        border-color: var(--border) !important;
    }

    .app-shell__logout-btn {
        color: var(--danger);
    }

    .app-shell__logout-btn i {
        color: var(--danger);
    }

    .app-shell__logout-btn:hover {
        background-color: var(--danger-soft);
        color: var(--danger);
    }

    .app-shell__menu-footer {
        padding: 0.35rem 0.5rem 0.5rem;
    }

    .app-shell__user-avatar {
        width: 36px;
        height: 36px;
        background: var(--brand-gradient);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        flex-shrink: 0;
    }

    .app-shell__content {
        padding: 2rem;
        flex: 1;
    }

    .app-shell__overlay {
        display: none;
    }

    @media (max-width: 991.98px) {
        .app-shell__sidebar {
            transform: translateX(-100%);
        }
        .app-shell__sidebar.open {
            transform: translateX(0);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.18);
        }
        .app-shell__main {
            margin-left: 0;
        }
        .app-shell__menu-toggle {
            display: flex;
        }
        .app-shell__overlay {
            display: block;
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.55);
            z-index: 1045;
            backdrop-filter: blur(2px);
        }
        .app-shell__content {
            padding: 1.25rem;
        }
        .app-shell__header {
            padding: 1rem;
        }
    }
</style>