<script lang="ts">
    import { inertia, router, useForm } from '@inertiajs/svelte';
    import { Badge, Button, Card, CardBody, CardHeader, Input, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    type Paket = { id: number; kode: string; nama: string; soals?: { id: number }[] };
    type Sesi = { id: number; nama: string; ruang?: { nama: string } | null };

    let {
        ujian, mode, langkah, maksLangkah, kelengkapan, kelompokBanks = [], ruangs = [],
    }: {
        ujian: {
            id: number; judul: string; kategori: string; durasi_menit: number;
            token?: string; tata_tertib?: string; kkm?: number;
            paket_soals?: Paket[]; sesis?: Sesi[];
            soals?: { id: number }[];
        };
        mode: 'santai' | 'resmi';
        langkah: number;
        maksLangkah: number;
        kelengkapan: string[];
        kelompokBanks?: { id: number; nama: string; soals_count: number }[];
        ruangs?: { id: number; nama: string; kapasitas?: number }[];
    } = $props();

    const judulLangkah: Record<string, string> = {
        'santai-2': 'Tambah Soal',
        'santai-3': 'Review & Terbitkan',
        'resmi-2': 'Paket Soal (Varian A/B/C)',
        'resmi-3': 'Sesi & Ruang',
        'resmi-4': 'Token & Tata Tertib',
        'resmi-5': 'Review & Terbitkan',
    };

    // Form paket (langkah 2 resmi) — pilih bank soal bernama.
    const paketForm = useForm({ jumlah_paket: 3, soal_per_paket: 10, kelompok_bank_soal_id: null as number | null });

    function generatePaket() {
        paketForm.post(`/app/guru/ujian/${ujian.id}/wizard/paket`);
    }

    // Form sesi (langkah 3 resmi)
    let sesiList = $state([{ nama: 'Sesi 1', waktu_mulai: '', waktu_selesai: '', ruang_id: null as number | null, kapasitas: null as number | null }]);
    function tambahSesi() {
        sesiList = [...sesiList, { nama: `Sesi ${sesiList.length + 1}`, waktu_mulai: '', waktu_selesai: '', ruang_id: null, kapasitas: null }];
    }
    function simpanSesi() {
        router.post(`/app/guru/ujian/${ujian.id}/wizard/sesi`, { sesis: sesiList });
    }

    // Form token (langkah 4 resmi)
    const tokenForm = useForm({
        tata_tertib: ujian.tata_tertib ?? '',
        kkm: ujian.kkm ?? 75,
    });
    function simpanToken() {
        tokenForm.post(`/app/guru/ujian/${ujian.id}/wizard/token`);
    }

    function terbit() {
        router.post(`/app/guru/ujian/${ujian.id}/wizard/terbit`);
    }

    function kembali() {
        router.visit('/app/guru/ujian');
    }
</script>

<PageHeader title={ujian.judul} subtitle="Langkah {langkah} dari {maksLangkah} — {judulLangkah[`${mode}-${langkah}`] ?? ''}" />

<div class="d-flex gap-2 mb-3">
    {#each Array(maksLangkah) as _, i}
        <div class="flex-fill rounded" style="height:6px;background:{i < langkah ? 'var(--bs-primary)' : 'var(--bs-secondary-bg)'}"></div>
    {/each}
</div>

{#if mode === 'santai' && langkah === 2}
    <Card>
        <CardHeader>Tambah Soal</CardHeader>
        <CardBody>
            <p class="text-muted">Soal saat ini: <strong>{ujian.soals?.length ?? 0}</strong></p>
            <p class="small text-muted">
                Tambahkan soal lewat halaman kelola soal (tombol di bawah), lalu kembali ke sini untuk lanjut.
                Bisa ketik manual, import Excel, atau ambil dari bank soal.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a use:inertia href="/app/guru/ujian/{ujian.id}/soal" class="btn btn-outline-primary">
                    <i class="bi bi-list-check me-1"></i>Kelola Soal
                </a>
                <Button color="primary" onclick={() => router.visit(`/app/guru/ujian/${ujian.id}/wizard/langkah/3?mode=santai`)}>
                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                </Button>
            </div>
        </CardBody>
    </Card>
{/if}

{#if mode === 'resmi' && langkah === 2}
    <Card>
        <CardHeader>Buat Paket Soal Otomatis</CardHeader>
        <CardBody>
            {#if (ujian.paket_soals?.length ?? 0) > 0}
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-1"></i>
                    {ujian.paket_soals!.length} paket sudah dibuat:
                    {#each ujian.paket_soals! as p}
                        <Badge color="info" class="me-1">Paket {p.kode} ({p.soals?.length ?? 0} soal)</Badge>
                    {/each}
                </div>
            {:else}
                <p class="text-muted small">
                    Pilih bank soal yang mau dipakai. Sistem akan membagi soalnya secara acak ke tiap paket.
                </p>
                <div class="mb-3">
                    <label class="form-label" for="pk-bank">Bank Soal <span class="text-danger">*</span></label>
                    <select id="pk-bank" class="form-select" bind:value={paketForm.kelompok_bank_soal_id}>
                        <option value={null}>— Pilih bank soal —</option>
                        {#each kelompokBanks as kb}
                            <option value={kb.id}>{kb.nama} ({kb.soals_count} soal)</option>
                        {/each}
                    </select>
                    {#if paketForm.errors.kelompok_bank_soal_id}
                        <div class="text-danger small">{paketForm.errors.kelompok_bank_soal_id}</div>
                    {/if}
                    {#if kelompokBanks.length === 0}
                        <div class="form-text">
                            Belum ada bank soal. <a use:inertia href="/app/guru/bank-soal/kelompok">Buat bank soal dulu</a>.
                        </div>
                    {/if}
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="pk-jumlah">Jumlah paket</label>
                        <input id="pk-jumlah" type="number" class="form-control" bind:value={paketForm.jumlah_paket} min="2" max="10" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pk-soal">Soal per paket</label>
                        <input id="pk-soal" type="number" class="form-control" bind:value={paketForm.soal_per_paket} min="1" max="100" />
                    </div>
                </div>
            {/if}
            <div class="d-flex gap-2 mt-3">
                {#if (ujian.paket_soals?.length ?? 0) === 0}
                    <Button color="primary" onclick={generatePaket} disabled={paketForm.processing}>
                        <i class="bi bi-magic me-1"></i>Generate {paketForm.jumlah_paket} Paket
                    </Button>
                {:else}
                    <Button color="primary" onclick={() => router.visit(`/app/guru/ujian/${ujian.id}/wizard/langkah/3?mode=resmi`)}>
                        Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </Button>
                {/if}
            </div>
        </CardBody>
    </Card>
{/if}

{#if mode === 'resmi' && langkah === 3}
    <Card>
        <CardHeader>Sesi Ujian & Ruang</CardHeader>
        <CardBody>
            {#if (ujian.sesis?.length ?? 0) > 0}
                <Table size="sm" striped>
                    <thead><tr><th>Sesi</th><th>Ruang</th></tr></thead>
                    <tbody>
                        {#each ujian.sesis! as s}
                            <tr><td>{s.nama}</td><td>{s.ruang?.nama ?? '–'}</td></tr>
                        {/each}
                    </tbody>
                </Table>
                <Button color="primary" onclick={() => router.visit(`/app/guru/ujian/${ujian.id}/wizard/langkah/4?mode=resmi`)}>
                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                </Button>
            {:else}
                {#each sesiList as s, i}
                    <div class="border rounded p-3 mb-2">
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
                                <select class="form-select" bind:value={s.ruang_id}>
                                    <option value={null}>— Pilih —</option>
                                    {#each ruangs as r}<option value={r.id}>{r.nama}</option>{/each}
                                </select>
                            </div>
                        </div>
                    </div>
                {/each}
                <div class="d-flex gap-2">
                    <Button color="secondary" outline onclick={tambahSesi}>
                        <i class="bi bi-plus me-1"></i>Tambah Sesi
                    </Button>
                    <Button color="primary" onclick={simpanSesi}>Simpan & Lanjut</Button>
                </div>
            {/if}
        </CardBody>
    </Card>
{/if}

{#if mode === 'resmi' && langkah === 4}
    <Card>
        <CardHeader>Token & Tata Tertib</CardHeader>
        <CardBody>
            {#if ujian.token}
                <div class="alert alert-info">
                    Token aktif: <strong class="fs-5">{ujian.token}</strong>
                </div>
            {/if}
            <div class="mb-3">
                <label class="form-label" for="tk-tertib">Tata Tertib <span class="text-danger">*</span></label>
                <textarea id="tk-tertib" class="form-control" rows="5" bind:value={tokenForm.tata_tertib}
                    placeholder={"1. Dilarang membuka tab/aplikasi lain.\n2. Dilarang bekerja sama.\n3. ..."}></textarea>
                {#if tokenForm.errors.tata_tertib}<div class="text-danger small">{tokenForm.errors.tata_tertib}</div>{/if}
            </div>
            <div class="mb-3">
                <label class="form-label" for="tk-kkm">KKM <span class="text-danger">*</span></label>
                <input id="tk-kkm" type="number" class="form-control" style="max-width:120px" bind:value={tokenForm.kkm} min="0" max="100" />
            </div>
            <Button color="primary" onclick={simpanToken} disabled={tokenForm.processing}>Simpan & Lanjut</Button>
        </CardBody>
    </Card>
{/if}

{#if (mode === 'santai' && langkah === 3) || (mode === 'resmi' && langkah === 5)}
    <Card>
        <CardHeader>Review & Terbitkan</CardHeader>
        <CardBody>
            <dl class="row small">
                <dt class="col-4">Judul</dt><dd class="col-8">{ujian.judul}</dd>
                <dt class="col-4">Kategori</dt><dd class="col-8">{ujian.kategori.toUpperCase()}</dd>
                <dt class="col-4">Durasi</dt><dd class="col-8">{ujian.durasi_menit} menit</dd>
                {#if mode === 'resmi'}
                    <dt class="col-4">Paket soal</dt><dd class="col-8">{ujian.paket_soals?.length ?? 0} paket</dd>
                    <dt class="col-4">Sesi</dt><dd class="col-8">{ujian.sesis?.length ?? 0} sesi</dd>
                    <dt class="col-4">Token</dt><dd class="col-8">{ujian.token ?? '–'}</dd>
                {:else}
                    <dt class="col-4">Soal</dt><dd class="col-8">{ujian.soals?.length ?? 0} soal</dd>
                {/if}
            </dl>

            {#if kelengkapan.length > 0}
                <div class="alert alert-warning">
                    <strong>Belum bisa diterbitkan:</strong>
                    <ul class="mb-0 ps-3">
                        {#each kelengkapan as k}<li>{k}</li>{/each}
                    </ul>
                </div>
            {:else}
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-1"></i>Semua lengkap. Siap diterbitkan!
                </div>
            {/if}

            <div class="d-flex gap-2">
                <Button color="secondary" outline onclick={kembali}>Simpan Draft</Button>
                <Button color="success" onclick={terbit} disabled={kelengkapan.length > 0}>
                    <i class="bi bi-send me-1"></i>Terbitkan Ujian
                </Button>
            </div>
        </CardBody>
    </Card>
{/if}
