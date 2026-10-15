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

    let { ujians }: { ujians: { data: Ujian[] } } = $props();
</script>

<PageHeader title="Panitia Ujian" subtitle="Kelola ujian resmi (UTS / UAS / USBK) lintas kelas" />

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
                        {#if u.status === 'terbit'}
                            <a use:inertia href="/app/panitia/ujian/{u.id}/monitoring" class="btn btn-sm btn-outline-primary mb-2">
                                <i class="bi bi-activity me-1"></i>Monitoring
                            </a>
                        {/if}
                        {#if !u.siap_terbit && u.status === 'draft'}
                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                <strong>Belum siap terbit:</strong>
                                <ul class="mb-0 ps-3">
                                    {#each u.kelengkapan as k}<li>{k}</li>{/each}
                                </ul>
                            </div>
                        {:else if u.siap_terbit && u.status === 'draft'}
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
