<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import { router } from '@inertiajs/svelte';
    import { inertia } from '@inertiajs/svelte';

    let { jadwal, events, kelasList, banksoalList }: {
        jadwal: any;
        events: { id: number; nama: string }[];
        kelasList: { id: number; nama: string }[];
        banksoalList: { id: number; nama: string }[];
    } = $props();

    let form = $state({
        nama: jadwal.nama,
        event_id: jadwal.event_id,
        kelompok_bank_soal_id: jadwal.kelompok_bank_soal_id,
        tanggal_ujian: jadwal.tanggal,
        durasi: jadwal.durasi_menit,
        mode: jadwal.mode as 'luring' | 'daring',
        group_ids: [...jadwal.kelas_ids] as number[],
        acak_soal: true,
        acak_opsi: true,
        aktifkan_token: true,
        tampilkan_nilai: true,
    });

    function toggleKelas(id: number) {
        if (form.group_ids.includes(id)) {
            form.group_ids = form.group_ids.filter(k => k !== id);
        } else {
            form.group_ids = [...form.group_ids, id];
        }
    }

    function simpan() {
        router.put(`/app/panitia/jadwal/${jadwal.id}`, {
            nama: form.nama,
            event_id: form.event_id,
            kelompok_bank_soal_id: form.kelompok_bank_soal_id,
            tanggal: form.tanggal_ujian,
            durasi_menit: form.durasi,
            mode: form.mode,
            kelas_ids: form.group_ids,
        });
    }

    function hapus() {
        if (confirm('Hapus jadwal ini?')) {
            router.delete(`/app/panitia/jadwal/${jadwal.id}`);
        }
    }
</script>

<svelte:head>
    <title>Edit Jadwal — Panitia</title>
</svelte:head>

<PageHeader title="Edit Jadwal" subtitle={jadwal.nama}>
    {#snippet actions()}
        <a use:inertia href="/app/panitia/jadwal" class="btn btn-sm btn-outline-secondary">Kembali</a>
    {/snippet}
</PageHeader>

<Card>
    <CardBody>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="event">Event</label>
            <select id="event" class="form-select" bind:value={form.event_id}>
                {#each events as e}
                    <option value={e.id}>{e.nama}</option>
                {/each}
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Mode ujian</label>
            <div>
                <label class="me-3"><input type="radio" bind:group={form.mode} value="luring" /> Luring</label>
                <label><input type="radio" bind:group={form.mode} value="daring" /> Daring</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Atau pilih kelas langsung</label>
            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                {#each kelasList as k}
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="k-{k.id}" checked={form.group_ids.includes(k.id)} onchange={() => toggleKelas(k.id)} />
                        <label class="form-check-label" for="k-{k.id}">{k.nama}</label>
                    </div>
                {/each}
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="banksoal">Banksoal</label>
            <select id="banksoal" class="form-select" bind:value={form.kelompok_bank_soal_id}>
                {#each banksoalList as b}
                    <option value={b.id}>{b.nama}</option>
                {/each}
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="nama">Nama ujian</label>
            <input id="nama" class="form-control" bind:value={form.nama} />
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="tanggal">Tanggal ujian</label>
            <input id="tanggal" type="date" class="form-control" bind:value={form.tanggal_ujian} />
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="durasi">Durasi (menit)</label>
            <input id="durasi" type="number" class="form-control" bind:value={form.durasi} min="1" />
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Setting ujian</label>
            <div class="border rounded p-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="acak-soal" bind:checked={form.acak_soal} />
                    <label class="form-check-label" for="acak-soal">Acak soal</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="acak-opsi" bind:checked={form.acak_opsi} />
                    <label class="form-check-label" for="acak-opsi">Acak opsi jawaban</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="token" bind:checked={form.aktifkan_token} />
                    <label class="form-check-label" for="token">Aktifkan token</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="nilai" bind:checked={form.tampilkan_nilai} />
                    <label class="form-check-label" for="nilai">Tampilkan nilai</label>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <Button color="danger" onclick={hapus}>Hapus</Button>
            <div>
                <a use:inertia href="/app/panitia/jadwal" class="btn btn-outline-secondary me-2">Batal</a>
                <Button color="primary" onclick={simpan}>Simpan</Button>
            </div>
        </div>
    </CardBody>
</Card>
