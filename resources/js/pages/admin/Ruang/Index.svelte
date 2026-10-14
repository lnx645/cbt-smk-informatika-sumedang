<script lang="ts">
    import CrudManager, {
        type CrudColumn,
        type CrudField,
    } from '@/components/crud/CrudManager.svelte';
    import RuangController from '@/actions/App/Http/Controllers/Admin/RuangController';

    type RuangItem = Record<string, unknown> & { id: number };

    let { ruangs }: { ruangs: RuangItem[] } = $props();

    const columns: CrudColumn[] = [
        { key: 'kode', label: 'Kode', badge: true },
        { key: 'nama', label: 'Nama Ruang' },
        { key: 'kapasitas', label: 'Kapasitas', center: true },
        { key: 'lokasi', label: 'Lokasi' },
    ];

    const fields: CrudField[] = [
        { name: 'kode', label: 'Kode Ruang', placeholder: 'Contoh: R101' },
        { name: 'nama', label: 'Nama Ruang', placeholder: 'Contoh: Ruang 101' },
        { name: 'kapasitas', label: 'Kapasitas', type: 'number', placeholder: '40' },
        { name: 'lokasi', label: 'Lokasi', placeholder: 'Contoh: Gedung A lantai 1' },
    ];
</script>

<CrudManager
    title="Ruang Ujian"
    subtitle="Kelola data ruang fisik untuk dipakai pada sesi ujian."
    {columns}
    {fields}
    items={ruangs ?? []}
    controller={RuangController}
    createLabel="Tambah Ruang"
    resourceName="Ruang"
/>
