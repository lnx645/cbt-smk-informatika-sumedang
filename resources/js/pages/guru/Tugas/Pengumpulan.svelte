<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import { PENGGUMPULAN_INFO } from '@/lib/tugas';
    import TugasController from '@/actions/App/Http/Controllers/Guru/TugasController';

    type TugasInfo = {
        id: number;
        judul: string;
        kelas: string | null;
        matpel: string | null;
        deadline: string | null;
        jenis_pengumpulan: keyof typeof PENGGUMPULAN_INFO;
        jumlah_siswa: number;
    };

    type SiswaPengumpulan = {
        nisn: string;
        nama: string;
        file_name: string | null;
        jawaban_teks: string | null;
        submitted_at: string | null;
        terlambat: boolean;
        pengumpulan_id: number | null;
    };

    let {
        tugas,
        siswas,
    }: { tugas: TugasInfo; siswas: SiswaPengumpulan[] } = $props();

    const terkumpul = $derived(
        siswas.filter((s) => s.submitted_at).length,
    );
    const terlambat = $derived(
        siswas.filter((s) => s.terlambat).length,
    );
    const belum = $derived(siswas.length - terkumpul);

    let dibuka = $state<string | null>(null);
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Pengumpulan Tugas"
        subtitle={`${tugas.judul} — ${tugas.kelas ?? 'Kelas'} · ${tugas.matpel ?? 'Matpel'}`}
    >
        {#snippet actions()}
            <a
                use:inertia
                href={TugasController.index().url}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Daftar Tugas
            </a>
        {/snippet}
    </PageHeader>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="text-muted small">Total Siswa</div>
                    <div class="h4 mb-0 fw-semibold">
                        {tugas.jumlah_siswa}
                    </div>
                </CardBody>
            </Card>
        </div>
        <div class="col-6 col-md-3">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="text-muted small">
                        Sudah Mengumpul
                    </div>
                    <div class="h4 mb-0 fw-semibold text-success">
                        {terkumpul}
                    </div>
                </CardBody>
            </Card>
        </div>
        <div class="col-6 col-md-3">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="text-muted small">Terlambat</div>
                    <div class="h4 mb-0 fw-semibold text-warning">
                        {terlambat}
                    </div>
                </CardBody>
            </Card>
        </div>
        <div class="col-6 col-md-3">
            <Card class="border rounded-1 shadow-none">
                <CardBody class="p-3">
                    <div class="text-muted small">
                        Belum Mengumpul
                    </div>
                    <div class="h4 mb-0 fw-semibold text-secondary">
                        {belum}
                    </div>
                </CardBody>
            </Card>
        </div>
    </div>

    <Card class="border rounded-1 shadow-none">
        <CardBody class="p-3">
            <div
                class="d-flex align-items-center gap-3 text-muted small mb-3"
            >
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-hourglass-split"></i>
                    Batas waktu:
                    <strong class="text-body"
                        >{tugas.deadline ?? '—'}</strong
                    >
                </span>
                <span class="d-flex align-items-center gap-2">
                    <i
                        class={`bi ${PENGGUMPULAN_INFO[tugas.jenis_pengumpulan].icon}`}
                    ></i>
                    Cara kumpul:
                    <strong class="text-body"
                        >{PENGGUMPULAN_INFO[tugas.jenis_pengumpulan]
                            .label}</strong
                    >
                </span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th>Status</th>
                            <th>Waktu Kumpul</th>
                            <th class="text-end">Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each siswas as siswa, i (siswa.nisn)}
                            <tr>
                                <td class="text-muted">{i + 1}</td>
                                <td class="fw-semibold"
                                    >{siswa.nama}</td
                                >
                                <td class="text-muted"
                                    >{siswa.nisn}</td
                                >
                                <td>
                                    {#if siswa.submitted_at}
                                        {#if siswa.terlambat}
                                            <Badge
                                                color="warning"
                                                pill>Terlambat</Badge
                                            >
                                        {:else}
                                            <Badge
                                                color="success"
                                                pill
                                                >Tepat Waktu</Badge
                                            >
                                        {/if}
                                    {:else}
                                        <Badge color="secondary" pill
                                            >Belum Kumpul</Badge
                                        >
                                    {/if}
                                </td>
                                <td class="small text-muted"
                                    >{siswa.submitted_at ?? '—'}</td
                                >
                                <td class="text-end">
                                    {#if siswa.pengumpulan_id}
                                        {#if siswa.file_name}
                                            <a
                                                href={TugasController.pengumpulanUnduh(
                                                    {
                                                        tugas: tugas.id,
                                                        pengumpulan:
                                                            siswa.pengumpulan_id,
                                                    },
                                                ).url}
                                                class="btn btn-sm btn-outline-primary"
                                                title="Unduh jawaban"
                                            >
                                                <i
                                                    class="bi bi-download me-1"
                                                ></i>
                                                {siswa.file_name}
                                            </a>
                                        {/if}
                                        {#if siswa.jawaban_teks}
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-link p-0 ms-2 align-baseline"
                                                onclick={() =>
                                                    (dibuka =
                                                        dibuka ===
                                                        siswa.nisn
                                                            ? null
                                                            : siswa.nisn)}
                                            >
                                                <i
                                                    class={`bi ${dibuka === siswa.nisn ? 'bi-chevron-up' : 'bi-chevron-down'} me-1`}
                                                ></i>
                                                Jawaban teks
                                            </button>
                                        {/if}
                                    {:else}
                                        <span class="text-muted small"
                                            >—</span
                                        >
                                    {/if}
                                </td>
                            </tr>
                            {#if dibuka === siswa.nisn && siswa.jawaban_teks}
                                <tr class="border-0">
                                    <td colspan="6" class="pt-0">
                                        <div
                                            class="jawaban-box mt-3 text-pre-wrap small"
                                        >
                                            {siswa.jawaban_teks}
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/each}
                    </tbody>
                </table>
            </div>
        </CardBody>
    </Card>
</div>

<style>
    .text-pre-wrap {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .jawaban-box {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-left: 4px solid var(--bs-success);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        animation: jawaban-masuk 0.18s ease;
    }

    @keyframes jawaban-masuk {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
