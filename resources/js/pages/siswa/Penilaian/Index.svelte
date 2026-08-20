<script lang="ts">
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type NilaiItem = {
        nama: string;
        tipe: string | null;
        sumber: 'manual' | 'tugas';
        nilai: number | null;
        nilai_maks: number | null;
    };

    type MatpelItem = {
        id: number;
        kelas: string | null;
        matpel: string | null;
        guru: string | null;
        nilai: NilaiItem[];
    };

    let { matpel }: { matpel: MatpelItem[] } = $props();

    const totalNilai = $derived(
        matpel.reduce(
            (total, m) =>
                total +
                m.nilai.filter((n) => n.nilai !== null).length,
            0,
        ),
    );
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Nilai"
        subtitle="Pantau nilai tugas dan penilaianmu dari semua mata pelajaran."
    />

    {#if matpel.length === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-clipboard-x display-5 d-block mb-2"></i>
                <div>Kamu belum terdaftar di kelas mana pun.</div>
            </CardBody>
        </Card>
    {:else if totalNilai === 0}
        <Card class="border rounded-1 shadow-none">
            <CardBody class="text-center text-muted py-5">
                <i class="bi bi-journal-x display-5 d-block mb-2"></i>
                <div>Belum ada nilai yang tercatat. Sabar ya, guru masih menilai.</div>
            </CardBody>
        </Card>
    {:else}
        <div class="row g-3">
            {#each matpel as m (m.id)}
                {#if m.nilai.length > 0}
                    <div class="col-12 col-xl-6">
                        <Card class="border rounded-1 shadow-none h-100">
                            <CardBody class="p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <div class="fw-semibold">{m.matpel ?? 'Matpel'}</div>
                                        <div class="small text-muted">
                                            <i class="bi bi-people me-1"></i>{m.kelas ?? 'Kelas'}
                                            <span class="mx-1">·</span>
                                            <i class="bi bi-person me-1"></i>{m.guru ?? 'Guru'}
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Sumber</th>
                                                <th class="text-end">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {#each m.nilai as n (n.nama + n.sumber)}
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{n.nama}</div>
                                                        {#if n.tipe && n.tipe !== 'tugas'}
                                                            <div class="text-muted small">{n.tipe}</div>
                                                        {/if}
                                                    </td>
                                                    <td>
                                                        {#if n.sumber === 'tugas'}
                                                            <Badge color="info" pill>Dari Tugas</Badge>
                                                        {:else}
                                                            <Badge color="light" pill>Manual</Badge>
                                                        {/if}
                                                    </td>
                                                    <td class="text-end text-nowrap">
                                                        {#if n.nilai !== null}
                                                            <span class="fw-semibold text-success">
                                                                <i class="bi bi-check2-circle me-1"></i>
                                                                {n.nilai}/{n.nilai_maks ?? '—'}
                                                            </span>
                                                        {:else}
                                                            <span class="text-muted small">
                                                                <i class="bi bi-hourglass me-1"></i>Belum dinilai
                                                            </span>
                                                        {/if}
                                                    </td>
                                                </tr>
                                            {/each}
                                        </tbody>
                                    </table>
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                {/if}
            {/each}
        </div>
    {/if}
</div>