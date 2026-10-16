<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Table, Modal, ModalBody, ModalFooter, ModalHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type Paket = { id: number; kode: string; nama: string; jumlah_soal: number };
    type Sesi = {
        id: number; nama: string; waktu_mulai?: string; waktu_selesai?: string;
        ruang_ujian_id?: number | null; ruang?: string | null; kapasitas?: number | null;
        token?: string | null; token_dibuka: boolean;
        peserta_nisns: string[]; jumlah_peserta: number;
    };

    let { ujian, ruangs, siswas, petaSesi, kelengkapan }: {
        ujian: {
            id: number; judul: string; kategori: string; status: string; durasi_menit: number;
            token?: string; tata_tertib?: string; kkm?: number; tampilkan_hasil?: boolean; kartu_diterbitkan: boolean;
            kelas?: string; matpel?: string; guru?: string;
            paket_soals: Paket[]; sesis: Sesi[];
        };
        ruangs: { id: number; nama: string; kapasitas?: number }[];
        siswas: { nisn: string; nama: string }[];
        petaSesi: Record<string, string>;
        kelengkapan: string[];
    } = $props();

    // Modal tambah/edit sesi
    let sesiModalOpen = $state(false);
    let editSesiId = $state<number | null>(null);
    let konfirmHapusSesiId = $state<number | null>(null);
    const sesiForm = useForm({
        nama: '',
        waktu_mulai: '',
        waktu_selesai: '',
        ruang_ujian_id: null as number | null,
    });

    function bukaTambahSesi() {
        editSesiId = null;
        sesiForm.reset();
        sesiForm.nama = `Sesi ${ujian.sesis.length + 1}`;
        sesiModalOpen = true;
    }

    function bukaEditSesi(s: Sesi) {
        editSesiId = s.id;
        sesiForm.nama = s.nama;
        sesiForm.waktu_mulai = s.waktu_mulai ?? '';
        sesiForm.waktu_selesai = s.waktu_selesai ?? '';
        sesiForm.ruang_ujian_id = s.ruang_ujian_id ?? null;
        sesiModalOpen = true;
    }

    function simpanSesi() {
        if (editSesiId) {
            sesiForm.put(`/app/panitia/ujian/${ujian.id}/sesi-satu/${editSesiId}`, {
                onSuccess: () => { sesiModalOpen = false; },
            });
        } else {
            sesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi-satu`, {
                onSuccess: () => { sesiModalOpen = false; },
            });
        }
    }

    function hapusSesi(id: number) {
        router.delete(`/app/panitia/ujian/${ujian.id}/sesi-satu/${id}`, {
            onSuccess: () => { konfirmHapusSesiId = null; },
        });
    }

    // Modal kelola peserta sesi
    let pesertaModalOpen = $state(false);
    let pesertaSesiId = $state<number | null>(null);
    let pesertaSesiNama = $state('');
    let pesertaCari = $state('');
    const pesertaForm = useForm({ nisns: [] as string[] });

    function bukaPesertaSesi(s: Sesi) {
        pesertaSesiId = s.id;
        pesertaSesiNama = s.nama;
        pesertaCari = '';
        pesertaForm.nisns = [...s.peserta_nisns];
        pesertaModalOpen = true;
    }

    function togglePeserta(nisn: string) {
        if (pesertaForm.nisns.includes(nisn)) {
            pesertaForm.nisns = pesertaForm.nisns.filter((n) => n !== nisn);
        } else {
            pesertaForm.nisns = [...pesertaForm.nisns, nisn];
        }
    }

    function simpanPeserta() {
        if (!pesertaSesiId) return;
        pesertaForm.post(`/app/panitia/ujian/${ujian.id}/sesi-satu/${pesertaSesiId}/peserta`, {
            onSuccess: () => { pesertaModalOpen = false; },
        });
    }

    const pesertaTampil = $derived(
        siswas.filter((s) =>
            !pesertaCari || s.nama.toLowerCase().includes(pesertaCari.toLowerCase()) || s.nisn.includes(pesertaCari),
        ),
    );

    const aturForm = useForm({ tata_tertib: ujian.tata_tertib ?? '', kkm: ujian.kkm ?? 75, tampilkan_hasil: ujian.tampilkan_hasil ?? false });
    const terbitKartuForm = useForm({});
    const bukaTokenForm = useForm({});
    const tutupTokenForm = useForm({});
    const buatTokenForm = useForm({});
    const tokenSesiForm = useForm({});
    function simpanPengaturan() {
        aturForm.post(`/app/panitia/ujian/${ujian.id}/pengaturan`);
    }

    function terbit() {
        router.post(`/app/panitia/ujian/${ujian.id}/terbit`);
    }

    let konfirmTerbit = $state(false);

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
        <a href="/app/panitia/ujian/{ujian.id}/kartu-peserta-kelas" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-printer me-1"></i>Kartu (Kelas)
        </a>
        <a href="/app/panitia/ujian/{ujian.id}/berita-acara" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-text me-1"></i>Berita Acara
        </a>
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
<Card class="mb-3" id="bagian-sesi">
    <CardHeader>
        <div class="d-flex justify-content-between align-items-center">
            <span>Jadwal Sesi & Ruang</span>
            <Button color="primary" size="sm" onclick={bukaTambahSesi}>
                <i class="bi bi-plus-lg me-1"></i>Tambah Sesi
            </Button>
        </div>
    </CardHeader>
    <CardBody>
        {#if ujian.sesis.length === 0}
            <div class="text-center text-muted py-4">
                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                Belum ada sesi. Klik "Tambah Sesi" untuk membuat jadwal.
            </div>
        {:else}
            <div class="table-responsive">
                <Table size="sm" striped hover>
                    <thead>
                        <tr>
                            <th>Sesi</th>
                            <th><i class="bi bi-calendar-event me-1"></i>Jadwal</th>
                            <th><i class="bi bi-geo-alt me-1"></i>Ruang</th>
                            <th><i class="bi bi-key me-1"></i>Token</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each ujian.sesis as s}
                            <tr>
                                <td><strong>{s.nama}</strong></td>
                                <td class="small">
                                    {#if s.waktu_mulai}
                                        <div><i class="bi bi-play-fill text-success me-1"></i>{s.waktu_mulai?.replace('T', ' ')}</div>
                                        <div><i class="bi bi-stop-fill text-danger me-1"></i>{s.waktu_selesai?.replace('T', ' ')}</div>
                                    {:else}
                                        <span class="text-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{s.ruang ?? 'Belum ditentukan'}</span>
                                    {#if s.jumlah_peserta > 0}
                                        <span class="badge bg-info ms-1" title="Siswa terjadwal">{s.jumlah_peserta} siswa</span>
                                    {/if}
                                    <div class="mt-1 d-flex gap-1 flex-wrap">
                                        <Button size="sm" color="outline-success" onclick={() => bukaPesertaSesi(s)} title="Atur siswa sesi ini">
                                            <i class="bi bi-people"></i>
                                        </Button>
                                        <a href="/app/panitia/ujian/{ujian.id}/sesi/{s.id}/kartu-peserta" target="_blank" class="btn btn-sm btn-outline-secondary" title="Kartu peserta">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="/app/panitia/ujian/{ujian.id}/sesi/{s.id}/daftar-hadir" target="_blank" class="btn btn-sm btn-outline-secondary" title="Daftar hadir">
                                            <i class="bi bi-list-check"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    {#if s.token}
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="fs-6">{s.token}</code>
                                            <span class="badge {s.token_dibuka ? 'text-bg-success' : 'text-bg-secondary'}">
                                                {s.token_dibuka ? 'BUKA' : 'TUTUP'}
                                            </span>
                                        </div>
                                        <div class="mt-1 d-flex gap-1">
                                            {#if s.token_dibuka}
                                                <button class="btn btn-sm btn-outline-danger" onclick={() => tokenSesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi/${s.id}/tutup-token`)}>
                                                    Tutup
                                                </button>
                                            {:else}
                                                <button class="btn btn-sm btn-success" onclick={() => tokenSesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi/${s.id}/buka-token`)}>
                                                    <i class="bi bi-unlock me-1"></i>Buka
                                                </button>
                                            {/if}
                                            <button class="btn btn-sm btn-outline-warning" onclick={() => tokenSesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi/${s.id}/buat-token`)} title="Buat baru">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </div>
                                    {:else}
                                        <button class="btn btn-sm btn-primary" onclick={() => tokenSesiForm.post(`/app/panitia/ujian/${ujian.id}/sesi/${s.id}/buat-token`)}>
                                            <i class="bi bi-key me-1"></i>Buat Token
                                        </button>
                                    {/if}
                                </td>
                                <td class="text-end text-nowrap">
                                    <Button size="sm" color="outline-primary" onclick={() => bukaEditSesi(s)} title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </Button>
                                    {#if konfirmHapusSesiId === s.id}
                                        <Button size="sm" color="danger" onclick={() => hapusSesi(s.id)} title="Ya, hapus">
                                            <i class="bi bi-check-lg"></i>
                                        </Button>
                                        <Button size="sm" color="outline-secondary" onclick={() => (konfirmHapusSesiId = null)} title="Batal">
                                            <i class="bi bi-x-lg"></i>
                                        </Button>
                                    {:else}
                                        <Button size="sm" color="outline-danger" onclick={() => (konfirmHapusSesiId = s.id)} title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </Button>
                                    {/if}
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </Table>
            </div>
        {/if}
    </CardBody>
</Card>

<!-- Modal Tambah/Edit Sesi -->
<Modal isOpen={sesiModalOpen} toggle={() => (sesiModalOpen = false)}>
    <ModalHeader toggle={() => (sesiModalOpen = false)}>
        {editSesiId ? 'Edit' : 'Tambah'} Sesi Ujian
    </ModalHeader>
    <ModalBody>
        {#if sesiForm.errors.nama}<div class="alert alert-danger py-2 small">{sesiForm.errors.nama}</div>{/if}
        <div class="mb-3">
            <label class="form-label" for="sesi-nama">Nama Sesi</label>
            <input id="sesi-nama" class="form-control" bind:value={sesiForm.nama} placeholder="cth: Sesi 1" />
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label" for="sesi-mulai">Waktu Mulai</label>
                <input id="sesi-mulai" type="datetime-local" class="form-control" bind:value={sesiForm.waktu_mulai} />
                {#if sesiForm.errors.waktu_mulai}<div class="text-danger small">{sesiForm.errors.waktu_mulai}</div>{/if}
            </div>
            <div class="col-6">
                <label class="form-label" for="sesi-selesai">Waktu Selesai</label>
                <input id="sesi-selesai" type="datetime-local" class="form-control" bind:value={sesiForm.waktu_selesai} />
                {#if sesiForm.errors.waktu_selesai}<div class="text-danger small">{sesiForm.errors.waktu_selesai}</div>{/if}
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label" for="sesi-ruang">Ruang Ujian</label>
            <select id="sesi-ruang" class="form-select" bind:value={sesiForm.ruang_ujian_id}>
                <option value={null}>— Pilih ruang —</option>
                {#each ruangs as r}
                    <option value={r.id}>{r.nama}</option>
                {/each}
            </select>
            {#if sesiForm.errors.ruang_ujian_id}<div class="text-danger small">{sesiForm.errors.ruang_ujian_id}</div>{/if}
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" outline onclick={() => (sesiModalOpen = false)}>Batal</Button>
        <Button color="primary" onclick={simpanSesi} disabled={sesiForm.processing || !sesiForm.nama || !sesiForm.waktu_mulai || !sesiForm.waktu_selesai}>
            {#if sesiForm.processing}
                <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
            {:else}
                <i class="bi bi-check-lg me-1"></i>Simpan
            {/if}
        </Button>
    </ModalFooter>
</Modal>

<!-- Modal Kelola Peserta Sesi -->
<Modal isOpen={pesertaModalOpen} toggle={() => (pesertaModalOpen = false)} size="lg">
    <ModalHeader toggle={() => (pesertaModalOpen = false)}>
        <i class="bi bi-people me-2"></i>Siswa — {pesertaSesiNama}
    </ModalHeader>
    <ModalBody>
        <div class="d-flex gap-2 mb-3">
            <input class="form-control" placeholder="Cari nama / NISN..." bind:value={pesertaCari} />
            <span class="badge bg-primary align-self-center text-nowrap">{pesertaForm.nisns.length} dipilih</span>
        </div>
        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
            {#each pesertaTampil as s}
                {@const sesiLain = petaSesi[s.nisn] && petaSesi[s.nisn] !== pesertaSesiNama ? petaSesi[s.nisn] : null}
                <button
                    type="button"
                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 {pesertaForm.nisns.includes(s.nisn) ? 'active' : ''}"
                    onclick={() => togglePeserta(s.nisn)}
                >
                    <i class="bi {pesertaForm.nisns.includes(s.nisn) ? 'bi-check-square-fill' : 'bi-square'}"></i>
                    <span class="flex-grow-1 text-start">
                        {s.nama}
                        {#if sesiLain}
                            <span class="badge bg-warning text-dark ms-1" title="Sekarang di {sesiLain}, akan pindah ke {pesertaSesiNama}">di {sesiLain}</span>
                        {/if}
                    </span>
                    <small class="{pesertaForm.nisns.includes(s.nisn) ? 'text-white-50' : 'text-muted'}">{s.nisn}</small>
                </button>
            {:else}
                <div class="text-muted text-center py-3">Tidak ada siswa yang cocok.</div>
            {/each}
        </div>
    </ModalBody>
    <ModalFooter>
        <Button color="secondary" outline onclick={() => (pesertaModalOpen = false)}>Batal</Button>
        <Button color="primary" onclick={simpanPeserta} disabled={pesertaForm.processing}>
            {#if pesertaForm.processing}
                <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
            {:else}
                <i class="bi bi-check-lg me-1"></i>Simpan ({pesertaForm.nisns.length})
            {/if}
        </Button>
    </ModalFooter>
</Modal>

<!-- Tata Tertib & KKM -->
<Card class="mb-3" id="bagian-token">
    <CardHeader>Tata Tertib & KKM</CardHeader>
    <CardBody>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle me-1"></i>
            Token sekarang diatur <strong>per sesi</strong> di tabel Jadwal Sesi & Ruang di atas.
        </div>
        <div class="mb-3">
            <label class="form-label" for="p-tertib">Tata Tertib <span class="text-danger">*</span></label>
            <textarea id="p-tertib" class="form-control" rows="5" bind:value={aturForm.tata_tertib}></textarea>
            {#if aturForm.errors.tata_tertib}<div class="text-danger small">{aturForm.errors.tata_tertib}</div>{/if}
        </div>
        <div class="mb-3">
            <label class="form-label" for="p-kkm">KKM <span class="text-danger">*</span></label>
            <input id="p-kkm" type="number" class="form-control" style="max-width:120px" bind:value={aturForm.kkm} min="0" max="100" />
        </div>
        <div class="mb-3 form-check form-switch">
            <input class="form-check-input" type="checkbox" id="p-tampilkan" bind:checked={aturForm.tampilkan_hasil} />
            <label class="form-check-label" for="p-tampilkan">Tampilkan nilai ke siswa setelah ujian</label>
            <div class="form-text">Jika dimatikan, siswa hanya melihat status "Selesai" tanpa nilai.</div>
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
                <strong><i class="bi bi-exclamation-triangle me-1"></i>Belum bisa diterbitkan — klik untuk perbaiki:</strong>
                <ul class="mb-0 ps-3 mt-2">
                    {#each kelengkapan as k}
                        {@const [target, pesan] = k.includes(':') ? k.split(/:(.+)/).filter(Boolean) : ['', k]}
                        {@const anchor = target === 'SESI' ? '#bagian-sesi' : target === 'TOKEN' ? '#bagian-token' : null}
                        <li class="mb-1">
                            {#if anchor}
                                <a href={anchor} class="alert-link">{pesan} <i class="bi bi-arrow-up-circle"></i></a>
                            {:else}
                                {pesan}
                            {/if}
                        </li>
                    {/each}
                </ul>
            </div>
        {:else}
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-1"></i>Semua lengkap. Siap diterbitkan!
            </div>
        {/if}
        <div class="d-flex gap-2 flex-wrap align-items-center">
            {#if !konfirmTerbit}
                <Button color="success" onclick={() => (konfirmTerbit = true)} disabled={kelengkapan.length > 0 || ujian.status === 'terbit'}>
                    <i class="bi bi-send me-1"></i>Terbitkan Ujian
                </Button>
            {:else}
                <span class="small text-muted">Yakin terbitkan? Siswa akan bisa melihat jadwalnya.</span>
                <Button color="success" onclick={terbit}>
                    <i class="bi bi-check-lg me-1"></i>Ya, Terbitkan
                </Button>
                <Button color="secondary" outline onclick={() => (konfirmTerbit = false)}>Batal</Button>
            {/if}
            {#if ujian.status === 'diajukan'}
                <input class="form-control" style="max-width:300px" bind:value={catatan} placeholder="Catatan revisi (opsional)" />
                <Button color="warning" outline onclick={kembalikan}>
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan ke Guru
                </Button>
            {/if}
        </div>
    </CardBody>
</Card>
