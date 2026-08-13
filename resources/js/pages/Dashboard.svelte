<script lang="ts">
    import IsRole from '@/components/IsRole.svelte';
    import { Container, Input } from '@sveltestrap/sveltestrap';
</script>

<Container>
    <IsRole role="siswa">
        <Input />
    </IsRole>
    <IsRole role="guru">lorem</IsRole>
</Container>
