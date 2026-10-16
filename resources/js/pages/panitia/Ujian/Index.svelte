<script lang="ts">
    import { inertia } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Ujian = {
        id: number; judul: string; kategori: string; status: string;
        paket_soals_count: number; sesis_count: number;
        kelengkapan: string[]; siap_terbit: boolean;
        guru_kelas?: { kelas?: { nama: string } } | null;
    };

    let { ujians, kelasList }: { ujians: { data: Ujian[] }; kelasList: { id: number; nama: string }[] } = $props();

    let pilihKelas = $state<number | null>(null);
    let showKartuModal = $state(false);

    function cetakKartuSemua() {
        if (!pilihKelas) return;
        window.open(`/app/panitia/kartu-peserta-semua?kelas_id=${pilihKelas}`, '_blank');
        showKartuModal = false;
    }
</script>

<PageHeader title="Panitia Ujian" subtitle="Kelola ujian resmi (UTS / UAS / USBK) lintas kelas">
    {#snippet actions()}
        <a use:inertia href="/app/panitia/ujian/buat" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Buat Ujian Resmi
        </a>
        <Button size="sm" color="outline-success" onclick={() => (showKartuModal = true)}>
            <i class="bi bi-person-badge me-1"></i>Kartu Semua Ujian
        </Button>
        <a href="/app/panitia/ujian/jadwal-pdf" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i>Cetak Jadwal
        </a>
    {/snippet}
</PageHeader>

{#if showKartuModal}
    <div class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kartu Peserta — Semua Ujian</h5>
                    <button type="button" class="btn-close" onclick={() => (showKartuModal = false)}></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Satu kartu per siswa berisi <strong>semua</strong> ujian yang sudah diterbitkan untuk kelas tersebut (seperti kartu UNBK).</p>
                    <label class="form-label" for="kartu-kelas">Pilih Kelas</label>
                    <select id="kartu-kelas" class="form-select" bind:value={pilihKelas}>
                        <option value={null}>-- Pilih kelas --</option>
                        {#each kelasList as k}
                            <option value={k.id}>{k.nama}</option>
                        {/each}
                    </select>
                </div>
                <div class="modal-footer">
                    <Button color="secondary" outline onclick={() => (showKartuModal = false)}>Batal</Button>
                    <Button color="success" onclick={cetakKartuSemua} disabled={!pilihKelas}>
                        <i class="bi bi-printer me-1"></i>Cetak Kartu
                    </Button>
                </div>
            </div>
        </div>
    </div>
{/if}

{#if ujians.data.length === 0}
    <EmptyState message="Belum ada ujian resmi" hint="Ujian resmi dibuat oleh guru/panitia dengan kategori UTS, UAS, atau USBK." />
{:else}
    <div class="row g-2 g-md-3">
        {#each ujians.data as u}
            <div class="col-12 col-lg-6">
                <Card class="h-100">
                    <CardHeader class="py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{u.judul}</span>
                        <Badge color={u.status === 'terbit' ? 'success' : 'secondary'}>{u.status}</Badge>
                    </CardHeader>
                    <CardBody class="py-2">
                        <div class="d-flex flex-wrap gap-2 small mb-2">
                            <Badge color="info">{u.kategori.toUpperCase()}</Badge>
                            {#if u.guru_kelas?.kelas}<span class="text-muted">{u.guru_kelas.kelas.nama}</span>{/if}
                        </div>
                        <div class="d-flex flex-wrap gap-2 small mb-2">
                            <span><i class="bi bi-layers me-1"></i>{u.paket_soals_count} paket</span>
                            <span><i class="bi bi-clock me-1"></i>{u.sesis_count} sesi</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a use:inertia href="/app/panitia/ujian/{u.id}" class="btn btn-sm btn-primary mb-2">
                                <i class="bi bi-gear me-1"></i>Atur Jadwal
                            </a>
                            {#if u.status === 'terbit'}
                                <a use:inertia href="/app/panitia/ujian/{u.id}/monitoring" class="btn btn-sm btn-outline-primary mb-2">
                                    <i class="bi bi-activity me-1"></i>Monitoring
                                </a>
                            {/if}
                        </div>
                        {#if !u.siap_terbit && (u.status === 'draft' || u.status === 'diajukan')}
                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                <strong>Belum siap terbit:</strong>
                                <ul class="mb-0 ps-3">
                                    {#each u.kelengkapan as k}<li>{k}</li>{/each}
                                </ul>
                            </div>
                        {:else if u.siap_terbit && u.status !== 'terbit'}
                            <div class="alert alert-success py-2 px-3 small mb-2">
                                <i class="bi bi-check-circle me-1"></i>Siap diterbitkan.
                            </div>
                        {/if}
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
{/if}
