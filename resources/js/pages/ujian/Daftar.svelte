<script lang="ts">
    import { router } from '@inertiajs/svelte';

    let { siswa, ujians }: {
        siswa: { nama: string; nisn: string };
        ujians: Array<{
            id: number;
            judul: string;
            kategori: string;
            matpel?: string;
            durasi_menit: number;
            jumlah_soal: number;
            sedang_berlangsung: boolean;
            butuh_token: boolean;
            sudah_selesai: boolean;
            sedang_dikerjakan: boolean;
        }>;
    } = $props();

    function logout() {
        router.post('/ujian/logout');
    }

    function kategoriLabel(k: string): string {
        const map: Record<string, string> = {
            uts: 'UTS',
            uas: 'UAS',
            usbk: 'USBK',
            uh: 'UH',
            quiz: 'Kuis',
        };
        return map[k] ?? k.toUpperCase();
    }
</script>

<svelte:head>
    <title>Daftar Ujian — CBT SMK Informatika Sumedang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</svelte:head>

<div class="anbk-daftar">
    <header class="anbk-header">
        <div class="anbk-header-inner">
            <div class="anbk-brand">
                <div class="anbk-logo"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="anbk-brand-text">
                    <div class="anbk-title">UJIAN BERBASIS KOMPUTER</div>
                    <div class="anbk-subtitle">SMK Informatika Sumedang</div>
                </div>
            </div>
            <button class="anbk-logout" onclick={logout} title="Keluar">
                <i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Keluar</span>
            </button>
        </div>
    </header>

    <div class="anbk-identity">
        <div class="anbk-identity-inner">
            <div class="anbk-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="anbk-identity-text">
                <div class="anbk-name">{siswa.nama}</div>
                <div class="anbk-nisn">NISN {siswa.nisn}</div>
            </div>
            <div class="anbk-status-pill">
                <span class="dot"></span> Terhubung
            </div>
        </div>
    </div>

    <main class="anbk-main">
        <div class="anbk-section-head">
            <h2><i class="bi bi-clipboard2-check me-2"></i>Daftar Ujian</h2>
            <p>Pilih ujian yang akan dikerjakan. Pastikan token sesi sudah diterima dari pengawas.</p>
        </div>

        {#if ujians.length === 0}
            <div class="anbk-empty">
                <i class="bi bi-inbox"></i>
                <h3>Belum ada ujian tersedia</h3>
                <p>Saat ini tidak ada jadwal ujian resmi untuk Anda. Hubungi panitia jika seharusnya ada ujian.</p>
            </div>
        {:else}
            <div class="anbk-list">
                {#each ujians as u}
                    <article class="anbk-exam">
                        <div class="anbk-exam-top">
                            <span class="anbk-kat">{kategoriLabel(u.kategori)}</span>
                            {#if u.sudah_selesai}
                                <span class="anbk-badge done"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                            {:else if u.sedang_dikerjakan}
                                <span class="anbk-badge doing"><i class="bi bi-pencil-fill me-1"></i>Sedang dikerjakan</span>
                            {:else if u.sedang_berlangsung}
                                <span class="anbk-badge live"><span class="pulse"></span>Berlangsung</span>
                            {:else}
                                <span class="anbk-badge idle">Terjadwal</span>
                            {/if}
                        </div>
                        <h3 class="anbk-exam-title">{u.judul}</h3>
                        <div class="anbk-exam-meta">
                            <span><i class="bi bi-book me-1"></i>{u.matpel ?? '-'}</span>
                            <span><i class="bi bi-list-ol me-1"></i>{u.jumlah_soal} soal</span>
                            <span><i class="bi bi-clock me-1"></i>{u.durasi_menit} menit</span>
                            {#if u.butuh_token}
                                <span class="anbk-token"><i class="bi bi-key-fill me-1"></i>Butuh token</span>
                            {/if}
                        </div>
                        {#if u.sudah_selesai}
                            <a href="/ujian/{u.id}/hasil" class="anbk-start secondary">
                                Lihat Hasil
                                <i class="bi bi-award-fill ms-2"></i>
                            </a>
                        {:else}
                            <a href="/ujian/konfirmasi/{u.id}" class="anbk-start">
                                {u.sedang_dikerjakan ? 'Lanjutkan Mengerjakan' : 'Mulai Ujian'}
                                <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                            </a>
                        {/if}
                    </article>
                {/each}
            </div>
        {/if}

        <div class="anbk-info">
            <div class="anbk-info-item">
                <i class="bi bi-key-fill"></i>
                <div><strong>Token sesi</strong><br />Diminta oleh pengawas saat ujian dimulai.</div>
            </div>
            <div class="anbk-info-item">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><strong>Jangan tutup halaman</strong><br />Selama ujian berlangsung, tetap di halaman ujian.</div>
            </div>
            <div class="anbk-info-item">
                <i class="bi bi-clock-history"></i>
                <div><strong>Waktu berjalan otomatis</strong><br />Ujian tersimpan otomatis saat waktu habis.</div>
            </div>
        </div>
    </main>

    <footer class="anbk-footer">SMK Informatika Sumedang · Sistem Ujian Resmi</footer>
</div>

<style>
    :global(body) { margin: 0 !important; padding: 0 !important; }
    :global(html) { margin: 0 !important; padding: 0 !important; }
    .anbk-daftar {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
        background: #eef2f7;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        margin: 0;
    }
    .anbk-header {
        background: linear-gradient(135deg, #0d47a1 0%, #1565c0 60%, #1e88e5 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(13, 71, 161, 0.35);
    }
    .anbk-header-inner {
        max-width: 860px;
        margin: 0 auto;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .anbk-brand { display: flex; align-items: center; gap: 12px; }
    .anbk-logo {
        width: 42px; height: 42px; border-radius: 10px;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.35);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem;
    }
    .anbk-title { font-weight: 800; letter-spacing: .06em; font-size: .95rem; }
    .anbk-subtitle { font-size: .78rem; opacity: .85; }
    .anbk-logout {
        border: 1px solid rgba(255,255,255,.5);
        background: rgba(255,255,255,.12);
        color: #fff; border-radius: 8px;
        padding: 7px 14px; font-size: .85rem; cursor: pointer;
    }
    .anbk-logout:hover { background: rgba(255,255,255,.22); }
    .anbk-identity { background: #fff; border-bottom: 1px solid #e2e8f0; }
    .anbk-identity-inner {
        max-width: 860px; margin: 0 auto; padding: 12px 20px;
        display: flex; align-items: center; gap: 12px;
    }
    .anbk-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        background: #dbeafe; color: #0d47a1;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .anbk-identity-text { flex: 1; }
    .anbk-name { font-weight: 700; color: #0f172a; font-size: .95rem; }
    .anbk-nisn { font-size: .78rem; color: #64748b; }
    .anbk-status-pill {
        font-size: .72rem; font-weight: 600; color: #15803d;
        background: #dcfce7; border-radius: 999px; padding: 5px 12px;
        display: flex; align-items: center; gap: 6px;
    }
    .anbk-status-pill .dot {
        width: 8px; height: 8px; border-radius: 50%; background: #22c55e;
        animation: blink 1.6s infinite;
    }
    @keyframes blink { 50% { opacity: .4; } }
    .anbk-main { max-width: 860px; width: 100%; margin: 0 auto; padding: 16px 0 24px; flex: 1; }
    .anbk-section-head { padding: 0 16px; }
    .anbk-section-head h2, .anbk-section-head p { padding: 0; }
    .anbk-list { padding: 0 12px; display: flex; flex-direction: column; gap: 12px; }
    .anbk-info { padding: 0 12px; }
    .anbk-empty { margin: 0 12px; border-radius: 12px; }
    .anbk-exam { border-radius: 12px; border: 1px solid #e2e8f0; border-left: 5px solid #0d47a1; }
    .anbk-section-head h2 { font-size: 1.1rem; font-weight: 700; color: #0d47a1; margin: 0 0 4px; padding: 0 4px; }
    .anbk-section-head p { font-size: .83rem; color: #64748b; margin: 0 0 14px; padding: 0 4px; }
    .anbk-empty {
        background: #fff; border-radius: 14px; padding: 48px 24px; text-align: center;
        box-shadow: 0 4px 14px rgba(13,71,161,.07);
    }
    .anbk-empty i { font-size: 2.6rem; color: #cbd5e1; }
    .anbk-empty h3 { font-size: 1.05rem; color: #334155; margin: 12px 0 6px; }
    .anbk-empty p { font-size: .85rem; color: #64748b; margin: 0; }
    .anbk-list { display: flex; flex-direction: column; gap: 14px; }
    .anbk-exam {
        background: #fff; border-radius: 12px; padding: 14px 16px;
        box-shadow: 0 4px 14px rgba(13,71,161,.07);
        border-left: 5px solid #0d47a1;
    }
    .anbk-exam-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
    .anbk-kat {
        font-size: .7rem; font-weight: 800; letter-spacing: .05em;
        background: #0d47a1; color: #fff; border-radius: 6px; padding: 3px 10px;
    }
    .anbk-badge { font-size: .72rem; font-weight: 600; border-radius: 999px; padding: 4px 11px; display: inline-flex; align-items: center; gap: 4px; }
    .anbk-badge.done { background: #dcfce7; color: #15803d; }
    .anbk-badge.doing { background: #fef3c7; color: #b45309; }
    .anbk-badge.live { background: #dbeafe; color: #0d47a1; }
    .anbk-badge.idle { background: #f1f5f9; color: #64748b; }
    .anbk-badge .pulse { width: 7px; height: 7px; border-radius: 50%; background: #1565c0; animation: blink 1.2s infinite; }
    .anbk-exam-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
    .anbk-exam-meta { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: .8rem; color: #64748b; margin-bottom: 14px; }
    .anbk-token { color: #b45309; font-weight: 600; }
    .anbk-start {
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #0d47a1, #1565c0); color: #fff;
        font-weight: 700; font-size: .9rem; text-decoration: none;
        border-radius: 9px; padding: 10px 22px; transition: filter .15s;
    }
    .anbk-start:hover { filter: brightness(1.08); color: #fff; }
    .anbk-start.secondary { background: linear-gradient(135deg, #047857, #059669); }
    .anbk-info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 22px; }
    .anbk-info-item {
        background: #fff; border-radius: 12px; padding: 14px;
        display: flex; gap: 10px; font-size: .76rem; color: #475569;
        box-shadow: 0 2px 8px rgba(13,71,161,.06);
    }
    .anbk-info-item i { color: #0d47a1; font-size: 1.1rem; flex-shrink: 0; margin-top: 2px; }
    .anbk-footer { text-align: center; font-size: .72rem; color: #94a3b8; padding: 14px; }
    @media (max-width: 640px) {
        .anbk-info { grid-template-columns: 1fr; }
        .anbk-exam-title { font-size: .98rem; }
        .anbk-daftar { background: #fff; }
        .anbk-exam, .anbk-info-item { box-shadow: none; border: 1px solid #e2e8f0; }
    }
</style>
