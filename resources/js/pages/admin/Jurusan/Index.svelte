<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import JurusanController from '@/actions/App/Http/Controllers/Admin/JurusanController';
    import { WhenVisible } from '@inertiajs/svelte';
    import { Badge } from '@sveltestrap/sveltestrap';

    type JurusanItem = Record<string, unknown> & { id: number };

    let {
        jurusans,
    }: {
        jurusans: {
            data: JurusanItem[];
            current_page: number;
            last_page: number;
        };
    } = $props();

    const columns: CrudColumn[] = [
        { key: 'kode', label: 'Kode', badge: true },
        { key: 'name', label: 'Nama Jurusan' },
        {
            key: 'jumlah_kelas',
            label: 'Jumlah Kelas',
            center: true,
            cell: detailGUru,
        },
    ];

    const fields: CrudField[] = [
        { name: 'kode', label: 'Kode Jurusan', placeholder: 'Contoh: TKJ' },
        
        {
            name: 'name',
            label: 'Nama Jurusan',
            placeholder: 'Contoh: Teknik Komputer & Jaringan',
        },
    ];

    let items = $derived(jurusans.data ?? []);
</script>

{#snippet detailGUru(item)}
    <Badge color="primary" type="sm">{item?.jumlah_kelas}</Badge>
{/snippet}
<CrudManager
    title="Jurusan"
    subtitle="Kelola data jurusan yang tersedia di sekolah."
    {columns}
    {fields}
    {items}
    actions={[{
        key:"OJ",
        icon:"bi bi-eye",
        onClick(item) {
            
        },
    }]}
    controller={JurusanController}
    createLabel="Tambah Jurusan"
    resourceName="Jurusan"
/>

{#if jurusans.current_page < jurusans.last_page}
    <WhenVisible
        data="jurusans"
        params={{ data: { page: jurusans.current_page + 1 } }}
        always
    >
        {#snippet children({ fetching })}
            {#if fetching}
                <div class="text-center text-muted small py-3">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat data…
                </div>
            {/if}
        {/snippet}
    </WhenVisible>
{/if}
