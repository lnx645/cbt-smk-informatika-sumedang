<script lang="ts">
    import VanillaDatePicker from '@/components/DatePicker/VanillaDatePicker.svelte';
import IsRole from '@/components/IsRole.svelte';
    import Select from '@/components/Select.svelte';
    import { Container, Input } from '@sveltestrap/sveltestrap';
    let collection = [
        {
            value: 'Rekayasa Perangkat Lunak',
            label: 'Rekayasa Perangkat Lunak',
        },
        { value: 'two', label: 'Two' },
        { value: 'three', label: 'Three' },
    ];
</script>

<Container>
    <IsRole role="siswa">
        <Select size="sm" items={collection} placeholder="foo" />
        <Input />
    </IsRole>
    <VanillaDatePicker/>
    <IsRole role="guru">lorem</IsRole>
</Container>
