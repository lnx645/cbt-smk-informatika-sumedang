<script lang="ts">
    import { inertia, router } from '@inertiajs/svelte';
    import { Button, Card, CardBody, CardHeader } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';

    let { events, groupList, kelasList, banksoalList, ruangList }: {
        events: { id: number; nama: string }[];
        groupList: { id: number; nama: string; kelas: { id: number; nama: string }[] }[];
        kelasList: { id: number; nama: string }[];
        banksoalList: { id: number; nama: string; matpel: string }[];
        ruangList: { id: number; nama: string; kapasitas: number }[];
    } = $props();

    let form = $state({
        event_id: '',
        mode: 'luring' as 'luring' | 'daring',
        group_ids: [] as number[],
        banksoal_id: '',
        nama_ujian: '',
        tanggal_ujian: '',
        durasi: 60,
        acak_soal: true,
        acak_opsi: true,
        aktifkan_token: true,
        tampilkan_nilai: true,
        sesi: ['08:00'] as string[],
        ruang_sesi: [''] as string[],
    });

    let errors = $state<Record<string, string>>({});
    let processing = $state(false);

    // Otomatis hitung jam sesi dari jam mulai + durasi
    function hitungSesiOtomatis() {
        const jamMulai = form.jam_mulai;
        const durasi = Number(form.durasi);
        if (!jamMulai || !durasi || durasi <= 0) return;
        const parts = jamMulai.split(':');
        if (parts.length < 2) return;
        const h = Number(parts[0]);
        const m = Number(parts[1]);
        if (isNaN(h) || isNaN(m)) return;
        let totalMenit = h * 60 + m;
        const hasil: string[] = [];
        for (let i = 0; i < 8; i++) {
            const jam = Math.floor(totalMenit / 60) % 24;
            const menit = totalMenit % 60;
            hasil.push(`${String(jam).padStart(2, '0')}:${String(menit).padStart(2, '0')}`);
            totalMenit += durasi;
        }
        form.sesi = hasil;
    }

    function toggleGroup(id: number) {
        if (form.group_ids.includes(id)) {
            form.group_ids = form.group_ids.filter(g => g !== id);
        } else {
            form.group_ids = [...form.group_ids, id];
        }
    }

    function submit() {
        processing = true;
        router.post('/app/panitia/jadwal', { ...form }, {
            onError: (e) => { errors = e as Record<string, string>; processing = false; },
            onFinish: () => { processing = false; },
        });
    }
</script>

<svelte:head>
    <title>Tambah Jadwal Ujian — Panitia</title>
</svelte:head>

<PageHeader title="Tambah Jadwal Ujian" subtitle="Buat jadwal ujian baru" />

<Card>
    <CardHeader class="bg-primary text-white">
        <i class="bi bi-calendar-plus me-2"></i>Tambah jadwal ujian
    </CardHeader>
    <CardBody>
        <form onsubmit={(e) => { e.preventDefault(); submit(); }}>
            <!-- Event -->
            <div class="mb-3">
                <label class="form-label fw-semibold" for="event">Event</label>
                <select id="event" class="form-select" bind:value={form.event_id}>
                    <option value="">Pilih event</option>
                    {#each events as ev}
                        <option value={ev.id}>{ev.nama}</option>
                    {/each}
                </select>
                {#if errors.event_id}<div class="text-danger small">{errors.event_id}</div>{/if}
            </div>

            <!-- Mode ujian -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Mode ujian</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="mode-luring" name="mode" value="luring" bind:group={form.mode} />
                        <label class="form-check-label" for="mode-luring">Luring (di sekolah)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="mode-daring" name="mode" value="daring" bind:group={form.mode} />
                        <label class="form-check-label" for="mode-daring">Daring (dari rumah)</label>
                    </div>
                </div>
                {#if errors.mode}<div class="text-danger small">{errors.mode}</div>{/if}
            </div>



            <!-- Atau pilih kelas langsung -->
            {#if !form.group_id}
            <div class="mb-3">
                <label class="form-label fw-semibold">Atau pilih kelas langsung</label>
                <div class="border rounded p-2 d-flex flex-wrap gap-2">
                    {#each kelasList as k}
                        <button
                            type="button"
                            class="btn btn-sm {form.group_ids.includes(k.id) ? 'btn-primary' : 'btn-outline-secondary'}"
                            onclick={() => toggleGroup(k.id)}
                        >
                            {k.nama}
                            {#if form.group_ids.includes(k.id)}<i class="bi bi-x ms-1"></i>{/if}
                        </button>
                    {/each}
                </div>
                {#if errors.group_ids}<div class="text-danger small">{errors.group_ids}</div>{/if}
            </div>
            {/if}

            <!-- Banksoal -->
            <div class="mb-3">
                <label class="form-label fw-semibold" for="banksoal">Banksoal</label>
                <select id="banksoal" class="form-select" bind:value={form.banksoal_id}>
                    <option value="">Pilih banksoal</option>
                    {#each banksoalList as b}
                        <option value={b.id}>{b.nama} — {b.matpel}</option>
                    {/each}
                </select>
                {#if errors.banksoal_id}<div class="text-danger small">{errors.banksoal_id}</div>{/if}
            </div>

            <!-- Nama ujian -->
            <div class="mb-3">
                <label class="form-label fw-semibold" for="nama">Nama ujian</label>
                <input id="nama" type="text" class="form-control" bind:value={form.nama_ujian} placeholder="UJIAN MATEMATIKA" style="text-transform: uppercase;" />
                {#if errors.nama_ujian}<div class="text-danger small">{errors.nama_ujian}</div>{/if}
            </div>

            <!-- Tanggal ujian -->
            <div class="mb-3">
                <label class="form-label fw-semibold" for="tanggal">Tanggal ujian</label>
                <input id="tanggal" type="date" class="form-control" bind:value={form.tanggal_ujian} />
                {#if errors.tanggal_ujian}<div class="text-danger small">{errors.tanggal_ujian}</div>{/if}
            </div>

            <!-- Durasi -->
            <div class="mb-3">
                <label class="form-label fw-semibold" for="durasi">Durasi (menit)</label>
                <input id="durasi" type="number" class="form-control" bind:value={form.durasi} min="1" />
            </div>


            <!-- Setting ujian -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Setting ujian</label>
                <div class="border rounded p-3 bg-light">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="acak-soal" bind:checked={form.acak_soal} />
                                <label class="form-check-label" for="acak-soal">Acak soal</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="token" bind:checked={form.aktifkan_token} />
                                <label class="form-check-label" for="token">Aktifkan token</label>
                            </div>

                        </div>
                        <div class="col-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="acak-opsi" bind:checked={form.acak_opsi} />
                                <label class="form-check-label" for="acak-opsi">Acak opsi</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="nilai" bind:checked={form.tampilkan_nilai} />
                                <label class="form-check-label" for="nilai">Tampilkan nilai</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jam mulai (sesi dihitung otomatis) -->
            <div class="mb-4">
                <label class="form-label fw-semibold" for="jam-mulai">
                    <i class="bi bi-info-circle me-1 text-muted"></i>Jam mulai sesi pertama
                </label>
                <input id="jam-mulai" type="time" class="form-control" bind:value={form.sesi[0]} />
                <div class="form-text">Sesi berikutnya dihitung otomatis (jumlah siswa ÷ kapasitas, jeda 30 menit).</div>
                {#if form.mode === 'luring'}
                    <label class="form-label fw-semibold mt-3" for="ruangan">Ruangan</label>
                    <select id="ruangan" class="form-select" bind:value={form.ruang_sesi[0]}>
                        <option value="">Pilih ruangan</option>
                        {#each ruangList as r}
                            <option value={r.id}>{r.nama} (kap. {r.kapasitas})</option>
                        {/each}
                    </select>
                {/if}
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/app/panitia/jadwal" class="btn btn-outline-secondary">Batal</a>
                <Button color="primary" type="submit" disabled={processing}>
                    {#if processing}<span class="spinner-border spinner-border-sm me-1"></span>{/if}
                    Simpan
                </Button>
            </div>
        </form>
    </CardBody>
</Card>
