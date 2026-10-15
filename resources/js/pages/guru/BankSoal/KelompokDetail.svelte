<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Modal, ModalBody, ModalFooter, ModalHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Soal = {
        id: number; tipe: string; pertanyaan: string; poin: number;
        opsi?: { id: number; teks: string; benar: boolean }[];
    };

    let { kelompok }: {
        kelompok: {
            id: number; nama: string; deskripsi?: string;
            matpel?: { name: string };
            soals: Soal[];
        };
    } = $props();

    let modalOpen = $state(false);
    let jawabanBenarSalah = $state(true);
    const form = useForm({
        tipe: 'pg',
        pertanyaan: '',
        poin: 10,
        opsi: [
            { teks: '', benar: true },
            { teks: '', benar: false },
        ] as { teks: string; benar: boolean }[],
        kunci_isian: '',
    });

    function tambahOpsi() {
        form.opsi = [...form.opsi, { teks: '', benar: false }];
    }

    function simpan() {
        // Untuk benar/salah, kirim opsi Benar & Salah otomatis.
        if (form.tipe === 'benar_salah') {
            form.opsi = [
                { teks: 'Benar', benar: jawabanBenarSalah === true },
                { teks: 'Salah', benar: jawabanBenarSalah === false },
            ];
        }
        form.post(`/app/guru/bank-soal/kelompok/${kelompok.id}/soal`, {
            onSuccess: () => { modalOpen = false; form.reset(); jawabanBenarSalah = true; },
        });
    }

    function hapusBank() {
        if (!confirm(`Hapus bank "${kelompok.nama}" beserta semua soalnya?`)) return;
        router.delete(`/app/guru/bank-soal/kelompok/${kelompok.id}`);
    }
</script>

<PageHeader title={kelompok.nama} subtitle="{kelompok.matpel?.name ?? ''} · {kelompok.soals.length} soal">
    {#snippet actions()}
        <Button color="primary" onclick={() => (modalOpen = true)}>
            <i class="bi bi-plus-lg me-1"></i>Tambah Soal
        </Button>
        <Button color="danger" outline onclick={hapusBank}>
            <i class="bi bi-trash me-1"></i>
        </Button>
    {/snippet}
</PageHeader>

{#if kelompok.deskripsi}<p class="text-muted">{kelompok.deskripsi}</p>{/if}

{#if kelompok.soals.length === 0}
    <EmptyState message="Bank masih kosong" hint="Klik 'Tambah Soal' untuk mengisi soal pertama." />
{:else}
    <div class="row g-2">
        {#each kelompok.soals as s, i}
            <div class="col-12">
                <Card>
                    <CardBody class="py-2">
                        <div class="d-flex gap-2 align-items-start">
                            <span class="fw-bold text-muted">{i + 1}.</span>
                            <div class="flex-grow-1">
                                <div class="d-flex gap-2 align-items-center mb-1">
                                    <Badge color="secondary">{s.tipe}</Badge>
                                    <span class="text-muted small">{s.poin} poin</span>
                                </div>
                                <div class="small">{@html s.pertanyaan}</div>
                                {#if s.opsi && s.opsi.length > 0}
                                    <div class="mt-1 small">
                                        {#each s.opsi as o}
                                            <div class={o.benar ? 'text-success fw-semibold' : 'text-muted'}>
                                                • {o.teks} {o.benar ? '✓' : ''}
                                            </div>
                                        {/each}
                                    </div>
                                {/if}
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        {/each}
    </div>
{/if}

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)} size="lg">
    <ModalHeader toggle={() => (modalOpen = false)}>Tambah Soal ke "{kelompok.nama}"</ModalHeader>
    <ModalBody>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="ts-tipe">Tipe Soal</label>
                <select id="ts-tipe" class="form-select" bind:value={form.tipe}>
                    <option value="pg">Pilihan Ganda</option>
                    <option value="multi">Pilihan Ganda Kompleks</option>
                    <option value="benar_salah">Benar / Salah</option>
                    <option value="isian">Isian</option>
                    <option value="esai">Esai</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ts-poin">Poin</label>
                <input id="ts-poin" type="number" class="form-control" bind:value={form.poin} min="1" />
            </div>
            <div class="col-12">
                <label class="form-label" for="ts-tanya">Pertanyaan</label>
                <textarea id="ts-tanya" class="form-control" rows="3" bind:value={form.pertanyaan}></textarea>
            </div>
            {#if form.tipe === 'pg' || form.tipe === 'multi'}
                <div class="col-12">
                    <label class="form-label">Opsi Jawaban (centang yang benar)</label>
                    {#each form.opsi as o, i}
                        <div class="input-group mb-2">
                            <div class="input-group-text">
                                <input type={form.tipe === 'pg' ? 'radio' : 'checkbox'} checked={o.benar}
                                    onchange={() => {
                                        if (form.tipe === 'pg') form.opsi = form.opsi.map((x, j) => ({ ...x, benar: j === i }));
                                        else o.benar = !o.benar;
                                    }} />
                            </div>
                            <input class="form-control" bind:value={o.teks} placeholder="Opsi {String.fromCharCode(65 + i)}" />
                        </div>
                    {/each}
                    <Button size="sm" color="secondary" outline onclick={tambahOpsi}>
                        <i class="bi bi-plus me-1"></i>Tambah Opsi
                    </Button>
                </div>
            {/if}
            {#if form.tipe === 'benar_salah'}
                <div class="col-12">
                    <label class="form-label">Jawaban yang benar</label>
                    <div class="d-flex gap-3">
                        <label class="d-flex align-items-center gap-2 border rounded px-3 py-2" style="cursor:pointer">
                            <input type="radio" name="bs-{kelompok.id}" checked={jawabanBenarSalah === true}
                                onchange={() => (jawabanBenarSalah = true)} />
                            <span class="fw-semibold text-success">Benar</span>
                        </label>
                        <label class="d-flex align-items-center gap-2 border rounded px-3 py-2" style="cursor:pointer">
                            <input type="radio" name="bs-{kelompok.id}" checked={jawabanBenarSalah === false}
                                onchange={() => (jawabanBenarSalah = false)} />
                            <span class="fw-semibold text-danger">Salah</span>
                        </label>
                    </div>
                </div>
            {/if}
            {#if form.tipe === 'isian'}
                <div class="col-12">
                    <label class="form-label" for="ts-kunci">Kunci Jawaban</label>
                    <input id="ts-kunci" class="form-control" bind:value={form.kunci_isian} />
                </div>
            {/if}
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" onclick={() => (modalOpen = false)}>Batal</Button>
        <Button color="primary" onclick={simpan} disabled={form.processing || !form.pertanyaan}>Simpan</Button>
    </ModalFooter>
</Modal>
