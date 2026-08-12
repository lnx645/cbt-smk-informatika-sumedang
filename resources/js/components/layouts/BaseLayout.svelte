<script lang="ts">
    import { usePage } from '@inertiajs/svelte';
    import { Link } from '@inertiajs/svelte';
    import {
        Navbar,
        NavbarBrand,
        Nav,
        NavItem,
        Container,
    } from '@sveltestrap/sveltestrap';
    import type { Snippet } from 'svelte';
    import AppHead from '../AppHead.svelte';

    let { children }: { children?: Snippet } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    const page = usePage();
    const onDashboard = $derived(page.url.startsWith('/dashboard'));
</script>

<AppHead />

<Navbar
    color="primary"
    dark
    expand="md"
    class="mb-4"
    style="border-bottom: 3px solid #fdd406"
>
    <Container fluid>
        <NavbarBrand href="/" class="fw-bold">{appName}</NavbarBrand>
        <Nav navbar class="ms-auto">
            <NavItem>
                <Link href="/" class="nav-link{!onDashboard ? ' active' : ''}"
                    >Home</Link
                >
            </NavItem>
            <NavItem>
                <Link
                    href="/manage"
                    class="nav-link{!onDashboard ? ' active' : ''}">Home</Link
                >
            </NavItem>
        </Nav>
    </Container>
</Navbar>

<main>
    {@render children?.()}
</main>
