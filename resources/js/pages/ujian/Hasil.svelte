<script lang="ts">
    import { router } from '@inertiajs/svelte';

    let { siswa, ujian, pengerjaan }: {
        siswa: { nama: string; nisn: string };
        ujian: { id: number; judul: string; kategori: string; nilai_maks: number; tampilkan_hasil: boolean };
        pengerjaan: {
            status: string;
            nilai_objektif: number | null;
            nilai_esai: number | null;
            nilai_total: number | null;
            submitted_at: string | null;
            menunggu_koreksi: boolean;
        };
    } = $props();

    function kembali() {
        router.visit('/ujian/daftar');
    }

    function statusLabel(s: string): string {
        const map: Record<string, string> = {
            selesai: 'Selesai',
            auto_submit: 'Otomatis (waktu habis)',
            diskualifikasi: 'Didiskualifikasi',
        };
        return map[s] ?? s;
    }
</script>

<svelte:head>
    <title>Hasil Ujian — {ujian.judul}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</svelte:head>

<div class="anbk-hasil">
    <header class="anbk-header">
        <div class="anbk-header-inner">
            <div class="anbk-brand">
                <div class="anbk-logo"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="anbk-brand-text">
                    <div class="anbk-title">UJIAN BERBASIS KOMPUTER</div>
                    <div class="anbk-subtitle">SMK Informatika Sumedang</div>
                </div>
            </div>
            <button class="anbk-back" onclick={kembali}>
                <i class="bi bi-arrow-left me-1"></i><span class="d-none d-sm-inline">Daftar Ujian</span>
            </button>
        </div>
    </header>

    <main class="anbk-main">
        <div class="anbk-result-card">
            <div class="anbk-result-icon {pengerjaan.status === 'diskualifikasi' ? 'danger' : 'success'}">
                <i class="bi {pengerjaan.status === 'diskualifikasi' ? 'bi-x-circle-fill' : 'bi-check-circle-fill'}"></i>
            </div>
            <h2>Ujian Selesai</h2>
            <p class="anbk-result-sub">{ujian.judul}</p>

            <div class="anbk-result-info">
                <div><span>Nama</span><strong>{siswa.nama}</strong></div>
                <div><span>NISN</span><strong>{siswa.nisn}</strong></div>
                <div><span>Status</span><strong>{statusLabel(pengerjaan.status)}</strong></div>
                {#if pengerjaan.submitted_at}
                    <div><span>Dikumpulkan</span><strong>{pengerjaan.submitted_at}</strong></div>
                {/if}
            </div>

            {#if ujian.tampilkan_hasil}
                {#if pengerjaan.menunggu_koreksi}
                    <div class="anbk-wait">
                        <i class="bi bi-hourglass-split me-2"></i>
                        Nilai sedang dikoreksi oleh guru. Silakan cek kembali nanti.
                    </div>
                {:else if pengerjaan.nilai_total !== null}
                    <div class="anbk-score">
                        <div class="anbk-score-label">Nilai Akhir</div>
                        <div class="anbk-score-value">{pengerjaan.nilai_total}</div>
                        <div class="anbk-score-max">dari {ujian.nilai_maks}</div>
                    </div>
                    <div class="anbk-score-detail">
                        {#if pengerjaan.nilai_objektif !== null}
                            <div><span>Objektif</span><strong>{pengerjaan.nilai_objektif}</strong></div>
                        {/if}
                        {#if pengerjaan.nilai_esai !== null}
                            <div><span>Esai</span><strong>{pengerjaan.nilai_esai}</strong></div>
                        {/if}
                    </div>
                {/if}
            {:else}
                <div class="anbk-wait">
                    <i class="bi bi-eye-slash-fill me-2"></i>
                    Nilai belum diumumkan. Hubungi guru/panitia untuk informasi nilai.
                </div>
            {/if}

            <button class="anbk-btn" onclick={kembali}>
                <i class="bi bi-list-check me-2"></i>Kembali ke Daftar Ujian
            </button>
        </div>
    </main>

    <footer class="anbk-footer">SMK Informatika Sumedang · Sistem Ujian Resmi</footer>
</div>

<style>
    :global(body) { margin: 0 !important; }
    :global(html) { margin: 0 !important; }
    .anbk-hasil { min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; background: #eef2f7; font-family: 'Segoe UI', system-ui, sans-serif; }
    .anbk-header { background: linear-gradient(135deg, #0d47a1 0%, #1565c0 60%, #1e88e5 100%); color: #fff; box-shadow: 0 2px 8px rgba(13,71,161,.35); }
    .anbk-header-inner { max-width: 640px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
    .anbk-brand { display: flex; align-items: center; gap: 12px; }
    .anbk-logo { width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
    .anbk-title { font-weight: 800; letter-spacing: .06em; font-size: .95rem; }
    .anbk-subtitle { font-size: .78rem; opacity: .85; }
    .anbk-back { border: 1px solid rgba(255,255,255,.5); background: rgba(255,255,255,.12); color: #fff; border-radius: 8px; padding: 7px 14px; font-size: .85rem; cursor: pointer; }
    .anbk-back:hover { background: rgba(255,255,255,.22); }
    .anbk-main { max-width: 640px; width: 100%; margin: 0 auto; padding: 24px 16px; flex: 1; }
    .anbk-result-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(13,71,161,.12); padding: 32px 28px; text-align: center; border-top: 5px solid #0d47a1; }
    .anbk-result-icon { font-size: 3.5rem; margin-bottom: 8px; }
    .anbk-result-icon.success { color: #16a34a; }
    .anbk-result-icon.danger { color: #dc2626; }
    .anbk-result-card h2 { font-size: 1.3rem; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
    .anbk-result-sub { color: #64748b; font-size: .9rem; margin: 0 0 20px; }
    .anbk-result-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: left; background: #f8fafc; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; }
    .anbk-result-info div span { display: block; font-size: .68rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .anbk-result-info div strong { font-size: .88rem; color: #0f172a; }
    .anbk-wait { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 10px; padding: 12px 14px; font-size: .85rem; margin-bottom: 18px; }
    .anbk-score { margin-bottom: 14px; }
    .anbk-score-label { font-size: .75rem; color: #64748b; text-transform: uppercase; letter-spacing: .06em; }
    .anbk-score-value { font-size: 3rem; font-weight: 800; color: #0d47a1; line-height: 1.1; }
    .anbk-score-max { font-size: .8rem; color: #64748b; }
    .anbk-score-detail { display: flex; gap: 10px; justify-content: center; margin-bottom: 18px; }
    .anbk-score-detail div { background: #f1f5f9; border-radius: 8px; padding: 8px 18px; }
    .anbk-score-detail span { display: block; font-size: .68rem; color: #64748b; }
    .anbk-score-detail strong { font-size: 1.1rem; color: #0d47a1; }
    .anbk-btn { width: 100%; border: none; border-radius: 9px; background: linear-gradient(135deg, #0d47a1, #1565c0); color: #fff; font-weight: 700; font-size: .95rem; padding: 12px; cursor: pointer; }
    .anbk-btn:hover { filter: brightness(1.08); }
    .anbk-footer { text-align: center; font-size: .72rem; color: #94a3b8; padding: 14px; }
    @media (max-width: 480px) { .anbk-result-info { grid-template-columns: 1fr; } }
</style>
