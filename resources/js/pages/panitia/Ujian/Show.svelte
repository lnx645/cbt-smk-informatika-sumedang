<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type Paket = { id: number; kode: string; nama: string; jumlah_soal: number };
    type Sesi = {
        id: number; nama: string; waktu_mulai?: string; waktu_selesai?: string;
        ruang_ujian_id?: number | null; ruang?: string | null; kapasitas?: number | null;
    };

    let { ujian, ruangs, kelengkapan }: {
        ujian: {
            id: number; judul: string; kategori: string; status: string; durasi_menit: number;
            token?: string; tata_tertib?: string; kkm?: number;
            kelas?: string; matpel?: string; guru?: string;
            paket_soals: Paket[]; sesis: Sesi[];
        };
        ruangs: { id: number; nama: string; kapasitas?: number }[];
        kelengkapan: string[];
    } = $props();

    type SesiForm = { nama: string; waktu_mulai: string; waktu_selesai: string; ruang_ujian_id: number | null; kapasitas: number | null };
    const sesiAwal: SesiForm[] = ujian.sesis.map((s) => ({
        nama: s.nama,
        waktu_mulai: s.waktu_mulai ?? '',
        waktu_selesai: s.waktu_selesai ?? '',
        ruang_ujian_id: s.ruang_ujian_id ?? null,
        kapasitas: s.kapasitas ?? null,
    }));
    const sesiForm = useForm({
        sesis: sesiAwal.length > 0 ? sesiAwal : [{ nama: 'Sesi 1', waktu_mulai: '', waktu_selesai: '', ruang_ujian_id: null, kapasitas: null } as SesiForm],
    });
    let editSesi = $state(sesiAwal.length === 0);

    function tambahSesi() {
        sesiForm.sesis = [...sesiForm.sesis, { nama: `Sesi ${sesiForm.sesis.length + 1}`, waktu_mulai: '', waktu_selesai: '', ruang_ujian_id: null, kapasitas: null }];
    }
    function hapusSesi(i: number) {
        sesiForm.sesis = sesiForm.sesis.filter((_, j) => j !== i);
    }
    function simpanSesi() {
        sesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi`);
    }

    const aturForm = useForm({ tata_tertib: ujian.tata_tertib ?? '', kkm: ujian.kkm ?? 75 });
    function simpanPengaturan() {
        aturForm.post(`/app/panitia/ujian/${ujian.id}/pengaturan`);
    }

    function terbit() {
        if (!confirm('Terbitkan ujian ini? Siswa akan bisa melihat jadwalnya.')) return;
        router.post(`/app/panitia/ujian/${ujian.id}/terbit`);
    }

    let catatan = $state('');
    function kembalikan() {
        if (!confirm('Kembalikan ujian ini ke guru untuk direvisi?')) return;
        router.post(`/app/panitia/ujian/${ujian.id}/kembalikan`, { catatan });
    }
</script>

<PageHeader title={ujian.judul} subtitle="{ujian.kelas ?? ''} · {ujian.matpel ?? ''} · Guru: {ujian.guru ?? '-'}">
    {#snippet actions()}
        <Badge color={ujian.status === 'terbit' ? 'success' : ujian.status === 'diajukan' ? 'warning' : 'secondary'}>
            {ujian.status.toUpperCase()}
        </Badge>
    {/snippet}
</PageHeader>

<!-- Ringkasan -->
<Card class="mb-3">
    <CardBody>
        <dl class="row small mb-0">
            <dt class="col-4 col-md-2">Kategori</dt><dd class="col-8 col-md-4">{ujian.kategori.toUpperCase()}</dd>
            <dt class="col-4 col-md-2">Durasi</dt><dd class="col-8 col-md-4">{ujian.durasi_menit} menit</dd>
            <dt class="col-4 col-md-2">Paket soal</dt>
            <dd class="col-8 col-md-4">
                {#each ujian.paket_soals as p}
                    <Badge color="info" class="me-1">Paket {p.kode} ({p.jumlah_soal})</Badge>
                {/each}
            </dd>
            <dt class="col-4 col-md-2">Token</dt><dd class="col-8 col-md-4">{ujian.token ?? '–'}</dd>
        </dl>
    </CardBody>
</Card>

<!-- Jadwal Sesi -->
<Card class="mb-3">
    <CardHeader>Jadwal Sesi & Ruang</CardHeader>
    <CardBody>
        {#if ujian.sesis.length > 0 && !editSesi}
            <Table size="sm" striped>
                <thead><tr><th>Sesi</th><th>Waktu</th><th>Ruang</th></tr></thead>
                <tbody>
                    {#each ujian.sesis as s}
                        <tr>
                            <td>{s.nama}</td>
                            <td class="small">{s.waktu_mulai?.replace('T', ' ')} – {s.waktu_selesai?.replace('T', ' ')}</td>
                            <td>{s.ruang ?? '–'}</td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
            <Button color="secondary" outline size="sm" onclick={() => (editSesi = true)}>
                <i class="bi bi-pencil me-1"></i>Ubah Jadwal
            </Button>
        {:else}
            {#if sesiForm.errors.sesis}<div class="text-danger small mb-2">{sesiForm.errors.sesis}</div>{/if}
            {#each sesiForm.sesis as s, i}
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small">Sesi {i + 1}</strong>
                        {#if sesiForm.sesis.length > 1}
                            <Button size="sm" color="danger" outline onclick={() => hapusSesi(i)}>
                                <i class="bi bi-trash"></i>
                            </Button>
                        {/if}
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Nama sesi</label>
                            <input class="form-control" bind:value={s.nama} />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mulai</label>
                            <input type="datetime-local" class="form-control" bind:value={s.waktu_mulai} />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Selesai</label>
                            <input type="datetime-local" class="form-control" bind:value={s.waktu_selesai} />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ruang</label>
                            <select class="form-select" bind:value={s.ruang_ujian_id}>
                                <option value={null}>— Pilih —</option>
                                {#each ruangs as r}<option value={r.id}>{r.nama}</option>{/each}
                            </select>
                        </div>
                    </div>
                </div>
            {/each}
            <div class="d-flex gap-2">
                <Button color="secondary" outline size="sm" onclick={tambahSesi}>
                    <i class="bi bi-plus me-1"></i>Tambah Sesi
                </Button>
                <Button color="primary" size="sm" onclick={simpanSesi} disabled={sesiForm.processing}>
                    Simpan Jadwal
                </Button>
            </div>
        {/if}
    </CardBody>
</Card>

<!-- Token, Tata Tertib, KKM -->
<Card class="mb-3">
    <CardHeader>Token, Tata Tertib & KKM</CardHeader>
    <CardBody>
        {#if ujian.token}
            <div class="alert alert-info">Token aktif: <strong class="fs-5">{ujian.token}</strong></div>
        {/if}
        <div class="mb-3">
            <label class="form-label" for="p-tertib">Tata Tertib <span class="text-danger">*</span></label>
            <textarea id="p-tertib" class="form-control" rows="5" bind:value={aturForm.tata_tertib}></textarea>
            {#if aturForm.errors.tata_tertib}<div class="text-danger small">{aturForm.errors.tata_tertib}</div>{/if}
        </div>
        <div class="mb-3">
            <label class="form-label" for="p-kkm">KKM <span class="text-danger">*</span></label>
            <input id="p-kkm" type="number" class="form-control" style="max-width:120px" bind:value={aturForm.kkm} min="0" max="100" />
        </div>
        <Button color="primary" onclick={simpanPengaturan} disabled={aturForm.processing}>Simpan</Button>
    </CardBody>
</Card>

<!-- Terbit -->
<Card>
    <CardHeader>Publikasi</CardHeader>
    <CardBody>
        {#if kelengkapan.length > 0}
            <div class="alert alert-warning">
                <strong>Belum bisa diterbitkan:</strong>
                <ul class="mb-0 ps-3">{#each kelengkapan as k}<li>{k}</li>{/each}</ul>
            </div>
        {:else}
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-1"></i>Semua lengkap. Siap diterbitkan!
            </div>
        {/if}
        <div class="d-flex gap-2 flex-wrap">
            <Button color="success" onclick={terbit} disabled={kelengkapan.length > 0 || ujian.status === 'terbit'}>
                <i class="bi bi-send me-1"></i>Terbitkan Ujian
            </Button>
            {#if ujian.status === 'diajukan'}
                <input class="form-control" style="max-width:300px" bind:value={catatan} placeholder="Catatan revisi (opsional)" />
                <Button color="warning" outline onclick={kembalikan}>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan ke Guru
                </Button>
            {/if}
        </div>
    </CardBody>
</Card>
