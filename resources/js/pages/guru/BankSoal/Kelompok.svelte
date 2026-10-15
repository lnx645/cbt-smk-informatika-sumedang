<script lang="ts">
    import { inertia, useForm } from '@inertiajs/svelte';
    import { Button, Card, CardBody, Modal, ModalBody, ModalFooter, ModalHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Kelompok = {
        id: number; nama: string; deskripsi?: string;
        matpel?: { name: string }; soals_count: number;
    };

    let { kelompoks, matpelOptions }: {
        kelompoks: { data: Kelompok[] };
        matpelOptions: { value: number; label: string }[];
    } = $props();

    let modalOpen = $state(false);
    const form = useForm({ nama: '', deskripsi: '', matpel_id: null as number | null });

    function buat() {
        form.post('/app/guru/bank-soal/kelompok', {
            onSuccess: () => { modalOpen = false; form.reset(); },
        });
    }
</script>

<PageHeader title="Bank Soal" subtitle="Buat wadah bank soal, lalu isi soal-soalnya">
    {#snippet actions()}
        <Button color="primary" onclick={() => (modalOpen = true)}>
            <i class="bi bi-plus-lg me-1"></i>Buat Bank Soal
        </Button>
    {/snippet}
</PageHeader>

{#if kelompoks.data.length === 0}
    <EmptyState message="Belum ada bank soal" hint="Klik 'Buat Bank Soal' untuk membuat wadah soal pertama." />
{:else}
    <div class="row g-2 g-md-3">
        {#each kelompoks.data as k}
            <div class="col-12 col-md-6 col-lg-4">
                <a use:inertia href="/app/guru/bank-soal/kelompok/{k.id}" class="text-decoration-none">
                    <Card class="h-100">
                        <CardBody>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-semibold mb-0">{k.nama}</h6>
                                <span class="badge bg-primary rounded-pill">{k.soals_count} soal</span>
                            </div>
                            {#if k.matpel}<div class="text-muted small mb-1">{k.matpel.name}</div>{/if}
                            {#if k.deskripsi}<p class="text-muted small mb-0">{k.deskripsi}</p>{/if}
                        </CardBody>
                    </Card>
                </a>
            </div>
        {/each}
    </div>
{/if}

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
    <ModalHeader toggle={() => (modalOpen = false)}>Buat Bank Soal Baru</ModalHeader>
    <ModalBody>
        <div class="mb-3">
            <label class="form-label" for="bk-nama">Nama Bank Soal <span class="text-danger">*</span></label>
            <input id="bk-nama" class="form-control" bind:value={form.nama}
                placeholder="cth: Bank Soal UTS Matematika 2026" />
            {#if form.errors.nama}<div class="text-danger small">{form.errors.nama}</div>{/if}
        </div>
        <div class="mb-3">
            <label class="form-label" for="bk-matpel">Mata Pelajaran <span class="text-danger">*</span></label>
            <select id="bk-matpel" class="form-select" bind:value={form.matpel_id}>
                <option value={null}>— Pilih —</option>
                {#each matpelOptions as m}<option value={m.value}>{m.label}</option>{/each}
            </select>
            {#if form.errors.matpel_id}<div class="text-danger small">{form.errors.matpel_id}</div>{/if}
        </div>
        <div class="mb-3">
            <label class="form-label" for="bk-deskripsi">Deskripsi (opsional)</label>
            <textarea id="bk-deskripsi" class="form-control" rows="2" bind:value={form.deskripsi}></textarea>
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
        <Button color="primary" onclick={buat} disabled={form.processing || !form.nama || !form.matpel_id}>
            Buat & Isi Soal
        </Button>
    </ModalFooter>
</Modal>
