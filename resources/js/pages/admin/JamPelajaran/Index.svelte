<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import JamPelajaranController from '@/actions/App/Http/Controllers/Admin/JamPelajaranController';
    import { Badge } from '@sveltestrap/sveltestrap';

    type JpItem = Record<string, unknown> & {
        id: number;
        urutan: number;
        is_break: boolean;
    };

    let {
        jpList,
        filters: activeFilters = {},
    }: {
        jpList: {
            data: JpItem[];
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            from?: number | null;
            to?: number | null;
        };
        filters?: Record<string, string>;
    } = $props();
    let haris = [
        {value:"Normal",label:"Normal"},
        { value: 'Senin', label: 'Senin' },
        { value: 'Selasa', label: 'Selasa' },
        { value: 'Rabu', label: 'Rabu' },
        { value: 'Kamis', label: 'Kamis' },
        { value: 'Jumat', label: 'Jumat' },
        { value: 'Sabtu', label: 'Sabtu' },
    ];
    const columns: CrudColumn[] = [
        { key: 'urutan', label: 'Urutan', center: true },
        { key: 'label', label: 'Label' },
        {
            key: 'jam_mulai',
            label: 'Jam Mulai',
            center: true,
        },
        {
            key: 'hari',
            label: 'Hari',
            center: true,
        },
        {
            key: 'jam_selesai',
            label: 'Jam Selesai',
            center: true,
        },
        {
            key: 'is_break',
            label: 'Tipe',
            center: true,
            badge: true,
            cell: IsBreak,
        },
    ];

    const fields: CrudField[] = [
        {
            name: 'label',
            label: 'Label',
            placeholder: 'JP 1, Istirahat 1, dst.',
        },
        {
            name: 'hari',
            type: 'select',
            label: 'Hari',
            placeholder: 'Select Hari',
            options: haris,
        },
        {
            name: 'jam_mulai',
            label: 'Jam Mulai',
            type: 'time',
            placeholder: 'HH:MM',
        },

        {
            name: 'jam_selesai',
            label: 'Jam Selesai',
            type: 'time',
            placeholder: 'HH:MM',
        },
        {
            name: 'is_break',
            label: 'Istirahat (bukan JP)',
            type: 'checkbox',
            placeholder: 'Tandai jika ini adalah waktu istirahat',
        },
        {
            name: 'urutan',
            label: 'Urutan',
            type: 'number',
            placeholder: 'Urutan urutan',
        },
    ];
</script>

{#snippet IsBreak(item)}
    {#if item.is_break}
        <Badge color="danger">ISTIRAHAT</Badge>
    {:else}
        <Badge color="success">JP</Badge>
    {/if}
{/snippet}

<CrudManager
    title="Jam Pelajaran"
    subtitle="Atur master jam pelajaran dan waktu istirahat (45 menit / istirahat)"
    {columns}
    {fields}
    items={jpList.data}
    controller={JamPelajaranController}
    resourceName="Jam Pelajaran"
    searchable
    searchPlaceholder="Cari jam pelajaran..."
    filters={[
        {
            name: 'is_break',
            label: 'Tipe',
            type: 'select',
            placeholder: 'Semua Tipe',
            options: [
                { value: '1', label: 'Istirahat' },
                { value: '0', label: 'JP' },
            ],
        },
        {
            name: 'hari',
            label: 'Hari',
            type:"select",
            placeholder: 'Default Senin',
            options: haris,
        },
    ]}
    query={activeFilters}
    only={['jpList']}
    pagination={jpList}
/>
