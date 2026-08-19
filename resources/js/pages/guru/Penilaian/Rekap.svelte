<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import PenilaianController from '@/actions/App/Http/Controllers/Guru/PenilaianController';

    type Penugasan = { value: number; label: string };

    type Kolom = {
        id: number;
        nama: string;
        nilai_maks: number | null;
    };

    type SiswaItem = {
        nisn: string;
        nama: string;
        nilai: (number | null)[];
        rata_rata: number | null;
    };

    let {
        penugasan,
        guruKelasInfo,
        selectedGuruKelasId,
        kolom,
        siswas,
    }: {
        penugasan: Penugasan[];
        guruKelasInfo: {
            id: number;
            kelas: string | null;
            matpel: string | null;
        } | null;
        selectedGuruKelasId: number | null;
        kolom: Kolom[] | null;
        siswas: SiswaItem[] | null;
    } = $props();

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

<div class="container-fluid px-0">
    <PageHeader
        title="Rekap Nilai"
        subtitle="Leger nilai siswa per kelas & mata pelajaran."
    >
        {#snippet actions()}
            <a
                use:inertia
                href={PenilaianController.index().url}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Input Nilai
            </a>
        {/snippet}
    </PageHeader>

    <Card class="border rounded-1 shadow-none mb-3">
        <CardBody class="p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="fw-semibold me-2">Penugasan:</div>
                <select
                    class="form-select"
                    style="min-width: 280px; max-width: 100%"
                    value={selectedGuruKelasId ?? ''}
                    onchange={changePenugasan}
                >
                    <option value="">-- Pilih kelas &amp; matpel --</option>
                    {#each penugasan as g (g.value)}
                        <option value={g.value}>{g.label}</option>
                    {/each}
                </select>
            </div>
            {#if guruKelasInfo}
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-people me-1"></i>
                    {guruKelasInfo.kelas ?? 'Kelas'} · {guruKelasInfo.matpel ?? 'Matpel'}
                </p>
            {/if}
        </CardBody>
    </Card>

    {#if guruKelasInfo}
        {#if !kolom || kolom.length === 0 || !siswas || siswas.length === 0}
            <Card class="border rounded-1 shadow-none">
                <CardBody class="text-center text-muted py-5">
                    <i class="bi bi-journal-x display-5 d-block mb-2"></i>
                    <div>
                        Belum ada nilai tercatat untuk penugasan ini.
                    </div>
                </CardBody>
            </Card>
        {:else}
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    {#each kolom as k (k.id)}
                                        <th class="text-end" title={k.nama}>
                                            <div class="text-truncate" style="max-width: 120px">
                                                {k.nama}
                                            </div>
                                            <div class="text-muted fw-normal small">
                                                maks {k.nilai_maks ?? '—'}
                                            </div>
                                        </th>
                                    {/each}
                                    <th class="text-end">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each siswas as siswa, i (siswa.nisn)}
                                    <tr>
                                        <td class="text-muted">{i + 1}</td>
                                        <td class="fw-semibold">{siswa.nama}</td>
                                        <td class="text-muted">{siswa.nisn}</td>
                                        {#each siswa.nilai as n, j (j)}
                                            <td class="text-end text-nowrap">
                                                {#if n !== null}
                                                    <span class="fw-semibold">{n}</span>
                                                {:else}
                                                    <span class="text-muted">—</span>
                                                {/if}
                                            </td>
                                        {/each}
                                        <td class="text-end text-nowrap">
                                            {#if siswa.rata_rata !== null}
                                                <Badge color="success" pill>
                                                    <i class="bi bi-calculator me-1"></i>
                                                    {siswa.rata_rata}
                                                </Badge>
                                            {:else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </CardBody>
            </Card>
        {/if}
    {:else}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-clipboard2-data display-5 d-block mb-2"></i>
                <div>Pilih penugasan untuk melihat rekap nilai.</div>
            </CardBody>
        </Card>
    {/if}
</div>