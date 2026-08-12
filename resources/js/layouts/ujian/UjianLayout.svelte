<script lang="ts">
    import { Alert, Container } from '@sveltestrap/sveltestrap';
    import type { Snippet } from 'svelte';
    import { Toaster } from 'svelte-sonner';

    let {
        children,
    }: {
        children: Snippet;
    } = $props();
</script>

<Toaster />
<Container>
<Alert>
    Belum ada ujian disini! Silahkan tunggu atau refresh halaman ini
</Alert>
    <div>
        {@render children?.()}
    </div>
</Container>
