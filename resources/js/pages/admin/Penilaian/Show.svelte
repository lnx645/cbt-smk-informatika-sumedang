<script lang="ts">
  import { router } from '@inertiajs/svelte';
  import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
  import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
  import { Card, CardBody, CardHeader, Table } from '@sveltestrap/sveltestrap';
  import PageHeader from '@/components/PageHeader.svelte';

  // Receive penilaian prop from controller
  let { penilaian }: { penilaian: any } = $props();
</script>

<PageHeader title="Detail Penilaian" subtitle={penilaian?.nama ?? ''} />

<Card class="mt-4">
  <CardHeader>
    Informasi Penilaian
  </CardHeader>
  <CardBody>
    <Table striped>
      <tbody>
        <tr><th>Nama</th><td>{penilaian?.nama}</td></tr>
        <tr><th>Deskripsi</th><td>{penilaian?.deskripsi ?? '-'}</td></tr>
        <tr><th>Tipe</th><td>{penilaian?.tipe}</td></tr>
        <tr><th>Nilai Maksimum</th><td>{penilaian?.nilai_maks}</td></tr>
        <tr><th>Bobot (%)</th><td>{penilaian?.bobot}</td></tr>
        <tr><th>Aktif</th><td>{penilaian?.aktif ? 'Ya' : 'Tidak'}</td></tr>
      </tbody>
    </Table>
    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-sm btn-success me-2" on:click={() => router.visit(DetailPenilaianController.filterSiswa({ penilaian: penilaian.id }).url)}>
        <i class="bi bi-pencil-square me-1"></i> Input Nilai
      </button>
      <button class="btn btn-sm btn-secondary me-2" on:click={() => router.visit(PenilaianController.edit({ penilaian: penilaian.id }).url)}>
        <i class="bi bi-pencil me-1"></i> Edit
      </button>
      <button class="btn btn-sm btn-primary" on:click={() => router.visit(PenilaianController.index().url)}>
        <i class="bi bi-arrow-left me-1"></i> Kembali
      </button>
    </div>
  </CardBody>
</Card>
