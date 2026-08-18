<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import {
        PENGGUMPULAN_INFO,
        STATUS_TUGAS_INFO,
        sisaWaktu,
    } from '@/lib/tugas';
    import TugasController from '@/actions/App/Http/Controllers/Siswa/TugasController';

    type TugasItem = {
        id: number;
        judul: string;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        tanggal_terbit: string | null;
        deadline: string | null;
        deadline_at: string | null;
        jenis_pengumpulan: keyof typeof PENGGUMPULAN_INFO;
        file_name: string | null;
        status: keyof typeof STATUS_TUGAS_INFO;
        submitted_at: string | null;
    };

    let { tugases }: { tugases: TugasItem[] } = $props();
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Tugas"
        subtitle="Kerjakan dan kumpulkan tugas sebelum batas waktu."
    />

    {#if tugases.length === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-clipboard-check display-5 d-block mb-2"></i>
                <div>Belum ada tugas untukmu. Santai dulu!</div>
            </CardBody>
        </Card>
    {:else}
        <div class="row g-3">
            {#each tugases as item (item.id)}
                <div class="col-12 col-md-6 col-xl-4">
                    <a
                        use:inertia
                        href={TugasController.show({ tugas: item.id }).url}
                        class="text-decoration-none"
                    >
                        <Card class="border rounded-1 shadow-none tugas-card h-100">
                            <CardBody class="p-3 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <span
                                        class="badge text-bg-light border text-body small fw-normal tugas-matpel"
                                    >
                                        <i class="bi bi-journal-bookmark me-1"></i
                                        ><span class="text-truncate">{item.matpel ?? 'Matpel'}</span>
                                    </span>
                                    <Badge
                                        color={STATUS_TUGAS_INFO[item.status].color}
                                        pill
                                        class="flex-shrink-0"
                                    >
                                        <i class={`bi ${STATUS_TUGAS_INFO[item.status].icon} me-1`}></i>
                                        {STATUS_TUGAS_INFO[item.status].label}
                                    </Badge>
                                </div>
                                <div class="fw-semibold text-body mb-1">{item.judul}</div>
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-people me-1"></i>{item.kelas ?? 'Kelas'}
                                    <span class="mx-1">·</span>
                                    <i class="bi bi-person me-1"></i>{item.guru ?? 'Guru'}
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class={`bi ${PENGGUMPULAN_INFO[item.jenis_pengumpulan].icon} me-1`}></i>
                                    Kumpul: {PENGGUMPULAN_INFO[item.jenis_pengumpulan].label}
                                </div>
                                <div class="mt-auto d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">
                                        <i class="bi bi-hourglass-split me-1"></i>{item.deadline ?? '—'}
                                    </span>
                                    {#if item.status === 'belum' && sisaWaktu(item.deadline_at)}
                                        <span class="text-primary fw-semibold">{sisaWaktu(item.deadline_at)}</span>
                                    {:else if item.submitted_at}
                                        <span class="text-success">
                                            <i class="bi bi-check2 me-1"></i>{item.submitted_at}
                                        </span>
                                    {/if}
                                </div>
                            </CardBody>
                        </Card>
                    </a>
                </div>
            {/each}
        </div>
    {/if}
</div>

<style>
    .tugas-matpel {
        min-width: 0;
        overflow: hidden;
    }

    .tugas-card {
        transition:
            border-color 0.15s ease-in-out,
            box-shadow 0.15s ease-in-out;
    }

    .tugas-card:hover {
        border-color: var(--bs-primary-border-subtle);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    }
</style>