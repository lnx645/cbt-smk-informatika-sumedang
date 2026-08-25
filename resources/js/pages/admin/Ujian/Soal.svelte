<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Card, CardBody } from '@sveltestrap/sveltestrap';
    import { KATEGORI_INFO, TIPE_SOAL_INFO, type KategoriUjian, type TipeSoal } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Admin/UjianController';

    type SoalItem = {
        id: number;
        tipe: TipeSoal;
        pertanyaan: string;
        poin: number;
        kunci_isian: string[] | null;
        opsi: { id: number; teks: string; benar: boolean }[];
    };

    let {
        ujian,
        soals,
    }: {
        ujian: {
            id: number;
            judul: string;
            kategori: KategoriUjian;
            status: string;
            guru: string | null;
            kelas: string | null;
            matpel: string | null;
            total_poin: number;
        };
        soals: SoalItem[];
    } = $props();
</script>

<div class="container-fluid px-0">
    <div class="mb-3">
        <a use:inertia href={UjianController.index().url} class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Monitor Ujian
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">
                {ujian.judul}
                <Badge color={KATEGORI_INFO[ujian.kategori].color} pill class="ms-1">
                    {KATEGORI_INFO[ujian.kategori].label}
                </Badge>
            </h1>
            <div class="text-muted small">
                {ujian.guru} · {ujian.kelas} · {ujian.matpel} · Total poin: {ujian.total_poin}
            </div>
        </div>
        <Badge color="secondary" pill><i class="bi bi-eye me-1"></i>Mode Pantau</Badge>
    </div>

    {#if soals.length === 0}
        <div class="text-center text-muted py-5">
            <i class="bi bi-card-list display-5 d-block mb-2"></i>
            <div>Ujian ini belum memiliki soal.</div>
        </div>
    {:else}
        {#each soals as s, i (s.id)}
            <Card class="border rounded-1 shadow-none mb-2">
                <CardBody class="p-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-semibold">Soal {i + 1}</span>
                        <Badge color="light" class="text-dark border">
                            <i class={`bi ${TIPE_SOAL_INFO[s.tipe].icon} me-1`}></i>
                            {TIPE_SOAL_INFO[s.tipe].label}
                        </Badge>
                        <span class="text-muted small">{s.poin} poin</span>
                    </div>
                    <div class="rich-deskripsi">{@html s.pertanyaan}</div>
                    {#if s.opsi.length}
                        <ul class="mt-2 mb-0 small">
                            {#each s.opsi as o (o.id)}
                                <li class={o.benar ? 'text-success fw-semibold' : ''}>
                                    {o.teks}
                                    {#if o.benar}<i class="bi bi-check-circle ms-1"></i>{/if}
                                </li>
                            {/each}
                        </ul>
                    {:else if s.kunci_isian?.length}
                        <div class="small text-muted mt-2">Kunci: {s.kunci_isian.join(', ')}</div>
                    {/if}
                </CardBody>
            </Card>
        {/each}
    {/if}
</div>
