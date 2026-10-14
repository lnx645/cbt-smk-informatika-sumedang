<script lang="ts">
    import { Badge, Card, CardBody, CardHeader, Table } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import EmptyState from '@/components/EmptyState.svelte';

    type Pengecoh = { opsi_id: number; teks: string; benar: boolean; dipilih: number; persentase: number };
    type HasilSoal = {
        soal_id: number; urutan: number; tipe: string; kode_kd: string | null;
        pertanyaan: string; jumlah_responden: number;
        taraf_kesukaran: number | null; kategori_kesukaran: string | null;
        daya_beda: number | null; kategori_daya_beda: string | null;
        pengecoh: Pengecoh[];
    };

    let { ujian, hasil }: {
        ujian: { id: number; judul: string };
        hasil: { soal: HasilSoal[]; reliabilitas: number | null; jumlah_responden: number };
    } = $props();

    function badgeKesukaran(k: string | null) {
        return k === 'sukar' ? 'danger' : k === 'sedang' ? 'warning' : k === 'mudah' ? 'success' : 'secondary';
    }
    function badgeDayaBeda(k: string | null) {
        return k === 'baik' ? 'success' : k === 'cukup' ? 'warning' : 'danger';
    }
</script>

<PageHeader title="Analisis Butir Soal — {ujian.judul}" />

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <Card><CardBody class="text-center">
            <div class="display-6">{hasil.jumlah_responden}</div>
            <div class="text-muted small">Responden</div>
        </CardBody></Card>
    </div>
    <div class="col-md-4">
        <Card><CardBody class="text-center">
            <div class="display-6">{hasil.reliabilitas ?? '–'}</div>
            <div class="text-muted small">Reliabilitas (Cronbach's α)</div>
        </CardBody></Card>
    </div>
    <div class="col-md-4">
        <Card><CardBody class="text-center">
            <div class="display-6">{hasil.soal.length}</div>
            <div class="text-muted small">Butir Soal</div>
        </CardBody></Card>
    </div>
</div>

<Card>
    <CardHeader>Hasil Analisis per Butir</CardHeader>
    <CardBody>
        {#if hasil.soal.length === 0}
            <EmptyState message="Belum ada data" hint="Analisis membutuhkan minimal satu pengerjaan yang selesai." />
        {:else}
            <Table responsive striped size="sm">
                <thead><tr>
                    <th>No</th><th>Soal</th><th>KD</th><th>Taraf Kesukaran</th><th>Daya Beda</th><th>Pengecoh</th>
                </tr></thead>
                <tbody>
                    {#each hasil.soal as s}
                        <tr>
                            <td>{s.urutan}</td>
                            <td style="max-width:260px">{s.pertanyaan} <span class="text-muted">({s.tipe})</span></td>
                            <td>{s.kode_kd ?? '–'}</td>
                            <td>
                                {#if s.taraf_kesukaran !== null}
                                    {s.taraf_kesukaran} <Badge color={badgeKesukaran(s.kategori_kesukaran)}>{s.kategori_kesukaran}</Badge>
                                {:else}–{/if}
                            </td>
                            <td>
                                {#if s.daya_beda !== null}
                                    {s.daya_beda} <Badge color={badgeDayaBeda(s.kategori_daya_beda)}>{s.kategori_daya_beda}</Badge>
                                {:else}–{/if}
                            </td>
                            <td style="max-width:280px">
                                {#if s.pengecoh.length === 0}<span class="text-muted">–</span>{/if}
                                {#each s.pengecoh as p}
                                    <div class="small {p.benar ? 'fw-bold text-success' : ''}">
                                        {p.teks}: {p.persentase}% {p.benar ? '✓' : ''}
                                    </div>
                                {/each}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </Table>
            <p class="text-muted small mt-2 mb-0">
                Taraf kesukaran: &lt;0,3 sukar · 0,3–0,7 sedang · &gt;0,7 mudah.
                Daya beda: ≥0,4 baik · 0,2–0,4 cukup · &lt;0,2 jelek (kelompok atas vs bawah 27%).
            </p>
        {/if}
    </CardBody>
</Card>
