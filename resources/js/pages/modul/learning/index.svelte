<script lang="ts">
    import DashboardGuru from './components/DashboardGuru.svelte';
    import DashboardSiswa from './components/DashboardSiswa.svelte';

    let { auth } = $props();
</script>

{#if auth?.user?.role == 'guru'}
    <DashboardGuru />
{:else}
    <DashboardSiswa />
{/if}
