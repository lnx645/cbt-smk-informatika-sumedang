<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Admin/PenilaianController';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { router } from '@inertiajs/svelte';

    interface Penugasan {
        value: number;
        label: string;
    }

    interface SiswaItem {
        nisn: string;
        nama: string;
        nilai: number | null;
        sumber: 'manual' | 'tugas' | null;
        keterangan: string | null;
        guru: string | null;
    }

    interface Props {
        penilaian: { id: number; nama: string };
        penugasan: Penugasan[];
        guruKelasInfo: { id: number; kelas: string | null; matpel: string | null } | null;
        selectedGuruKelasId: string | null;
        siswas: SiswaItem[] | null;
    }

    let {
        penilaian,
        penugasan = [],
        guruKelasInfo = null,
        selectedGuruKelasId = null,
        siswas = null,
    }: Props = $props();

    function changePenugasan(event: Event) {
        const value = (event.currentTarget as HTMLSelectElement).value;
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set('guru_kelas_id', value);
        } else {
            url.searchParams.delete('guru_kelas_id');
        }
        router.visit(url.pathname + url.search);
    }
</script>

<PageHeader
    title="Input Nilai"
    subtitle={`Penilaian: ${penilaian?.nama ?? ''}`}
/>

<Card class="mb-4 border rounded-1 shadow-none">
    <CardBody class="p-3">
        <div class="fw-semibold mb-3">Pilih Penugasan</div>
        <select
            class="form-select"
            value={selectedGuruKelasId ?? ''}
            onchange={changePenugasan}
            style="min-width: 240px"
        >
            <option value="">-- Pilih Penugasan --</option>
            {#each penugasan as g (g.value)}
                <option value={g.value}>{g.label}</option>
            {/each}
        </select>

        {#if guruKelasInfo}
            <p class="text-muted small mt-2 mb-0">
                <i class="bi bi-people me-1"></i>
                Menampilkan siswa {guruKelasInfo.kelas ?? ''} · {guruKelasInfo.matpel ?? ''}
            </p>
        {/if}
    </CardBody>
</Card>

{#if guruKelasInfo && siswas && siswas.length > 0}
    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>Nilai</th>
                            <th>Sumber</th>
                            <th>Guru</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each siswas as s, i (s.nisn)}
                            <tr>
                                <td class="text-muted">{i + 1}</td>
                                <td>{s.nisn}</td>
                                <td class="fw-semibold">{s.nama}</td>
                                <td>
                                    {#if s.nilai !== null}
                                        <span class="fw-semibold"
                                            >{s.nilai}</span
                                        >
                                    {:else}
                                        <span class="text-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    {#if s.sumber === 'tugas'}
                                        <span
                                            class="badge text-bg-info rounded-pill"
                                            >Dari Tugas</span
                                        >
                                    {:else if s.sumber === 'manual'}
                                        <span
                                            class="badge text-bg-light border rounded-pill"
                                            >Manual</span
                                        >
                                    {:else}
                                        <span class="text-muted">—</span>
                                    {/if}
                                </td>
                                <td class="text-muted small"
                                    >{s.guru ?? '—'}</td
                                >
                                <td class="text-end">
                                    <a
                                        href={DetailPenilaianController.detail(
                                            {
                                                penilaian: penilaian.id,
                                                guruKelas:
                                                    guruKelasInfo.id,
                                                siswa: s.nisn,
                                            },
                                        ).url}
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="bi bi-pencil me-1"></i>
                                        Input Nilai
                                    </a>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </CardBody>
    </Card>
{:else if guruKelasInfo}
    <Card class="border rounded-1 shadow-none">
        <CardBody>
            <p class="text-muted mb-0">
                Tidak ada siswa aktif ditemukan untuk penugasan yang
                dipilih.
            </p>
        </CardBody>
    </Card>
{/if}

<div class="mt-4">
    <Button
        color="secondary"
        outline
        size="sm"
        onclick={() => router.visit(PenilaianController.index().url)}
    >
        <i class="bi bi-arrow-left me-1"></i>
        Kembali ke Daftar Penilaian
    </Button>
</div>