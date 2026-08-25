<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import { KATEGORI_INFO, type KategoriUjian } from '@/lib/ujian';
    import UjianController from '@/actions/App/Http/Controllers/Siswa/UjianController';

    type UjianItem = {
        id: number;
        judul: string;
        kategori: KategoriUjian;
        matpel: string | null;
        guru: string | null;
        durasi_menit: number;
        jumlah_soal: number;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        sedang_berlangsung: boolean;
        butuh_token: boolean;
        attempt_terpakai: number;
        maks_attempt: number;
        sedang_dikerjakan: boolean;
        tampilkan_hasil: boolean;
        nilai_total: number | null;
    };

    let { ujians }: { ujians: UjianItem[] } = $props();

    const tokenForm = useForm({ token: '' });
    let tokenUntuk = $state<number | null>(null);

    function mulai(item: UjianItem) {
        if (item.butuh_token) {
            tokenUntuk = tokenUntuk === item.id ? null : item.id;
            return;
        }
        router.post(UjianController.mulai({ ujian: item.id }).url);
    }

    function submitToken(item: UjianItem) {
        tokenForm.post(UjianController.mulai({ ujian: item.id }).url, {
            onSuccess: () => tokenForm.reset(),
        });
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Ujian"
        subtitle="Kerjakan kuis dan ujian dari guru sesuai jadwal."
    />

    {#if ujians.length === 0}
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal-x display-5 d-block mb-2"></i>
            <div>Belum ada ujian untuk kelasmu saat ini.</div>
        </div>
    {:else}
        <div class="row g-3">
            {#each ujians as item (item.id)}
                <div class="col-md-6 col-xl-4">
                    <Card class="border rounded-1 shadow-none h-100">
                        <CardBody class="p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <Badge color={KATEGORI_INFO[item.kategori].color} pill>
                                    {KATEGORI_INFO[item.kategori].label}
                                </Badge>
                                {#if item.butuh_token}
                                    <Badge color="secondary" pill>
                                        <i class="bi bi-key me-1"></i>Token
                                    </Badge>
                                {/if}
                            </div>
                            <h2 class="h6 fw-semibold mb-1">{item.judul}</h2>
                            <div class="text-muted small mb-2">
                                {item.matpel ?? 'Matpel'} · {item.guru ?? 'Guru'}
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-clock me-1"></i>{item.durasi_menit} menit ·
                                {item.jumlah_soal} soal
                            </div>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-calendar3 me-1"></i>{item.tanggal_mulai ?? 'Kapan saja'}
                                {#if item.tanggal_selesai}
                                    – {item.tanggal_selesai}
                                {/if}
                            </div>

                            <div class="mt-auto">
                                {#if item.sedang_dikerjakan}
                                    <a
                                        use:inertia
                                        href={UjianController.kerjakan({ ujian: item.id }).url}
                                        class="btn btn-warning w-100"
                                    >
                                        <i class="bi bi-play-fill me-1"></i>Lanjutkan
                                    </a>
                                {:else if item.attempt_terpakai >= item.maks_attempt}
                                    <div class="d-grid gap-2">
                                        <Button color="secondary" disabled>
                                            Kesempatan habis
                                        </Button>
                                        {#if item.tampilkan_hasil}
                                            <a
                                                use:inertia
                                                href={UjianController.hasil({ ujian: item.id }).url}
                                                class="btn btn-outline-info btn-sm"
                                            >
                                                Lihat Hasil{item.nilai_total !== null
                                                    ? ` (${item.nilai_total})`
                                                    : ''}
                                            </a>
                                        {/if}
                                    </div>
                                {:else if !item.sedang_berlangsung}
                                    <Button color="secondary" class="w-100" disabled>
                                        Belum dibuka / sudah berakhir
                                    </Button>
                                {:else if tokenUntuk === item.id}
                                    <div class="input-group">
                                        <input
                                            class="form-control text-uppercase"
                                            placeholder="Token"
                                            bind:value={tokenForm.token}
                                        />
                                        <Button color="primary" onclick={() => submitToken(item)}>
                                            Mulai
                                        </Button>
                                    </div>
                                    {#if tokenForm.errors.token}
                                        <div class="text-danger small mt-1">
                                            {tokenForm.errors.token}
                                        </div>
                                    {/if}
                                {:else}
                                    <Button color="primary" class="w-100" onclick={() => mulai(item)}>
                                        <i class="bi bi-play-fill me-1"></i>Mulai
                                    </Button>
                                {/if}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            {/each}
        </div>
    {/if}
</div>
