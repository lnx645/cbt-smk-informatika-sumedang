<script lang="ts">
import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
import CrudManager from '@/components/crud/CrudManager.svelte';
import type {
    CrudColumn,
    CrudField,
} from '@/components/crud/CrudManager.svelte';
import { Badge, Button } from '@sveltestrap/sveltestrap';
    let fields: CrudField[] = [
        {
            name: 'nama_lengkap',
            label: 'Nama',
            placeholder: 'Contoh: Ahmad Badawi Syah Agus',
        },

        {
            name: 'pendidikan_terakhir',
            label: 'Pendidikan Terakhir',
            placeholder: 'Contoh: Ahmad Badawi Syah Agus',
        },

        {
            name: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            type: 'select',
            options: [
                {
                    value: 'L',
                    label: 'Laki Laki',
                },
                {
                    value: 'P',
                    label: 'Perempuan',
                },
            ],
        },
        {
            name: 'alamat',
            label: 'Alamat',
            placeholder: 'Ketikan Alamat',
            type: 'textarea',
        },
        {
            name: 'foto_profil',
            type: 'file',
            accept: 'image/*',
            label: 'Foto',
        },
        {
            name: 'is_aktif',
            type: 'checkbox',
            label: 'Aktif',
        },
    ];

    const columns: CrudColumn[] = [
        {
            key: 'nama_lengkap',
            label: 'Nama Lengkap',
        },
        {
            key: 'jenis_kelamin',
            label: 'Jenis Kelamin',
        },
        {
            key: 'pendidikan_terakhir',
            label: 'Pendidikan',
        },
        {
            key: 'walikelas',
            label: 'Wali Kelas',
            cell: walikelasCell,
        },

        {
            key: 'is_aktif',
            label: 'Aktif',
            cell: status,
        },
    ];

    let { pengajar, filters = {}, pendidikanOptions = [] } = $props();

    let items = $derived(pengajar?.data);
</script>

{#snippet status(item)}
    {#if item.is_aktif}
        <Badge color="success">Aktif</Badge>
    {:else}
        <Badge color="danger">Nonaktif</Badge>
    {/if}
{/snippet}

{#snippet walikelasCell(item)}
    {#if item.walikelas && item.walikelas.length}
        <div class="d-flex flex-wrap gap-1">
            {#each item.walikelas as k (k.id)}
                <Badge color="info">{k.nama}</Badge>
            {/each}
        </div>
    {:else}
        <span class="text-muted">Bukan Wali kelas</span>
    {/if}
{/snippet}

<CrudManager
    title="Pengajar"
    {columns}
    {items}
    subtitle="Kelola Pengajar Disini / Guru"
    controller={PengajarController}
    createLabel="Tambah"
    searchPlaceholder="Cari Guru"
    searchable
    pagination={pengajar}
    resourceName="Pengajar"
    query={filters}
    filters={[
        {
            name: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: 'L', label: 'Laki-laki' },
                { value: 'P', label: 'Perempuan' },
            ],
        },
        {
            name: 'is_aktif',
            label: 'Status',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: '1', label: 'Aktif' },
                { value: '0', label: 'Nonaktif' },
            ],
        },
        {
            name: 'pendidikan_terakhir',
            label: 'Pendidikan',
            type: 'select',
            placeholder: 'Semua',
            options: pendidikanOptions.map((p: string) => ({
                value: p,
                label: p,
            })),
        },
        {
            name: 'walikelas',
            label: 'Wali Kelas',
            type: 'select',
            placeholder: 'Semua',
            options: [
                { value: '1', label: 'Wali Kelas' },
                { value: '0', label: 'Bukan Wali Kelas' },
            ],
        },
    ]}
    {fields}
/>
