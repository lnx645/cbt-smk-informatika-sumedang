<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';

    let { siswa, ujian }: {
        siswa: { nama: string; nisn: string };
        ujian: {
            id: number;
            judul: string;
            kategori: string;
            matpel?: string;
            guru?: string;
            deskripsi?: string;
            tata_tertib?: string;
            durasi_menit: number;
            jumlah_soal: number;
            butuh_token: boolean;
            wajib_fullscreen: boolean;
            maks_pelanggaran: number;
            sesi_nama?: string;
        };
    } = $props();

    const form = useForm({
        token: '',
        setuju_tata_tertib: false,
    });

    let showToken = $state(false);

    function submit() {
        form.post(`/ujian/mulai/${ujian.id}`);
    }

    function kembali() {
        router.visit('/ujian/daftar');
    }

    function kategoriLabel(k: string): string {
        const map: Record<string, string> = { uts: 'UTS', uas: 'UAS', usbk: 'USBK' };
        return map[k] ?? k.toUpperCase();
    }
</script>

<svelte:head>
    <title>Konfirmasi Ujian — {ujian.judul}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</svelte:head>

<div class="anbk-konfirmasi">
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
        <!-- Identitas peserta -->
        <section class="anbk-panel">
            <div class="anbk-panel-head"><i class="bi bi-person-badge me-2"></i>Identitas Peserta</div>
            <div class="anbk-ident-grid">
                <div><span>Nama</span><strong>{siswa.nama}</strong></div>
                <div><span>NISN</span><strong>{siswa.nisn}</strong></div>
                {#if ujian.sesi_nama}
                    <div><span>Sesi</span><strong>{ujian.sesi_nama}</strong></div>
                {/if}
            </div>
        </section>

        <!-- Informasi ujian -->
        <section class="anbk-panel">
            <div class="anbk-panel-head"><i class="bi bi-clipboard-data me-2"></i>Informasi Ujian</div>
            <div class="anbk-ujian-head">
                <span class="anbk-kat">{kategoriLabel(ujian.kategori)}</span>
                <h2>{ujian.judul}</h2>
                <div class="anbk-meta">
                    <span><i class="bi bi-book me-1"></i>{ujian.matpel ?? '-'}</span>
                    <span><i class="bi bi-list-ol me-1"></i>{ujian.jumlah_soal} soal</span>
                    <span><i class="bi bi-clock me-1"></i>{ujian.durasi_menit} menit</span>
                </div>
                {#if ujian.deskripsi}
                    <p class="anbk-desc">{ujian.deskripsi}</p>
                {/if}
            </div>

            {#if ujian.tata_tertib}
                <div class="anbk-tatib">
                    <div class="anbk-tatib-title"><i class="bi bi-megaphone-fill me-2"></i>Tata Tertib Pengerjaan</div>
                    <div class="anbk-tatib-body">{@html ujian.tata_tertib}</div>
                </div>
            {/if}

            <div class="anbk-warnings">
                {#if ujian.wajib_fullscreen}
                    <div class="anbk-warn">
                        <i class="bi bi-fullscreen me-2"></i>
                        <span>Mode layar penuh <strong>wajib</strong>. Keluar dari layar penuh tercatat sebagai pelanggaran (maks. {ujian.maks_pelanggaran}x).</span>
                    </div>
                {/if}
                <div class="anbk-warn">
                    <i class="bi bi-eye-slash-fill me-2"></i>
                    <span>Dilarang membuka tab/aplikasi lain selama ujian berlangsung.</span>
                </div>
            </div>
        </section>

        <!-- Form mulai -->
        <section class="anbk-panel">
            <div class="anbk-panel-head"><i class="bi bi-play-circle-fill me-2"></i>Mulai Mengerjakan</div>

            {#if form.errors.token}
                <div class="anbk-alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{form.errors.token}</div>
            {/if}
            {#if form.errors.setuju_tata_tertib}
                <div class="anbk-alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{form.errors.setuju_tata_tertib}</div>
            {/if}

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    submit();
                }}
            >
                {#if ujian.butuh_token}
                    <div class="anbk-field">
                        <label for="token">Token Sesi <span class="req">*</span></label>
                        <div class="anbk-token-wrap">
                            <input
                                id="token"
                                type={showToken ? 'text' : 'password'}
                                placeholder="Minta token kepada pengawas"
                                bind:value={form.token}
                                required
                                autocomplete="off"
                                maxlength="10"
                            />
                            <button type="button" class="anbk-eye" onclick={() => (showToken = !showToken)} aria-label="Tampilkan token">
                                <i class="bi {showToken ? 'bi-eye-slash' : 'bi-eye'}"></i>
                            </button>
                        </div>
                        <div class="anbk-hint">Token diumumkan oleh pengawas di ruang ujian, <strong>tidak</strong> tercetak di kartu.</div>
                    </div>
                {/if}

                <label class="anbk-check">
                    <input type="checkbox" bind:checked={form.setuju_tata_tertib} />
                    <span>Saya telah membaca informasi ujian dan <strong>menyetujui tata tertib</strong> di atas.</span>
                </label>

                <div class="anbk-actions">
                    <button type="button" class="anbk-btn secondary" onclick={kembali}>Kembali</button>
                    <button type="submit" class="anbk-btn primary" disabled={form.processing || !form.setuju_tata_tertib}>
                        {#if form.processing}
                            <span class="spinner-border spinner-border-sm me-2"></span>Memulai…
                        {:else}
                            <i class="bi bi-play-fill me-2"></i>Mulai Ujian
                        {/if}
                    </button>
                </div>
            </form>
        </section>
    </main>

    <footer class="anbk-footer">SMK Informatika Sumedang · Sistem Ujian Resmi</footer>
</div>

<style>
    :global(body) { margin: 0 !important; padding: 0 !important; }
    :global(html) { margin: 0 !important; padding: 0 !important; }
    .anbk-konfirmasi { min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; background: #eef2f7; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; margin: 0; }
    .anbk-header { background: linear-gradient(135deg, #0d47a1 0%, #1565c0 60%, #1e88e5 100%); color: #fff; box-shadow: 0 2px 8px rgba(13,71,161,.35); }
    .anbk-header-inner { max-width: 860px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
    .anbk-brand { display: flex; align-items: center; gap: 12px; }
    .anbk-logo { width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
    .anbk-title { font-weight: 800; letter-spacing: .06em; font-size: .95rem; }
    .anbk-subtitle { font-size: .78rem; opacity: .85; }
    .anbk-back { border: 1px solid rgba(255,255,255,.5); background: rgba(255,255,255,.12); color: #fff; border-radius: 8px; padding: 7px 14px; font-size: .85rem; cursor: pointer; }
    .anbk-back:hover { background: rgba(255,255,255,.22); }
    .anbk-main { max-width: 860px; width: 100%; margin: 0 auto; padding: 20px 20px 30px; flex: 1; display: flex; flex-direction: column; gap: 16px; }
    .anbk-panel { background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(13,71,161,.07); overflow: hidden; }
    .anbk-panel-head { background: #0d47a1; color: #fff; font-weight: 700; font-size: .9rem; padding: 11px 18px; }
    .anbk-ident-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 16px 18px; }
    .anbk-ident-grid div span { display: block; font-size: .7rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .anbk-ident-grid div strong { font-size: .95rem; color: #0f172a; }
    .anbk-ujian-head { padding: 16px 18px 6px; }
    .anbk-kat { font-size: .7rem; font-weight: 800; letter-spacing: .05em; background: #0d47a1; color: #fff; border-radius: 6px; padding: 3px 10px; }
    .anbk-ujian-head h2 { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin: 10px 0 8px; }
    .anbk-meta { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: .83rem; color: #64748b; }
    .anbk-desc { font-size: .85rem; color: #475569; margin: 10px 0 4px; }
    .anbk-tatib { margin: 12px 18px; border: 1px solid #fde68a; background: #fffbeb; border-radius: 10px; overflow: hidden; }
    .anbk-tatib-title { font-weight: 700; font-size: .85rem; color: #92400e; padding: 10px 14px; border-bottom: 1px solid #fde68a; }
    .anbk-tatib-body { padding: 12px 14px; font-size: .83rem; color: #57534e; }
    .anbk-warnings { padding: 4px 18px 16px; display: flex; flex-direction: column; gap: 8px; }
    .anbk-warn { display: flex; font-size: .8rem; color: #7c2d12; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 9px 12px; }
    .anbk-warn i { color: #ea580c; margin-top: 1px; }
    .anbk-panel form { padding: 16px 18px 18px; }
    .anbk-alert { background: #fdecea; border: 1px solid #f5c6cb; color: #a12622; border-radius: 8px; padding: 10px 12px; font-size: .83rem; margin-bottom: 12px; }
    .anbk-field { margin-bottom: 14px; }
    .anbk-field label { display: block; font-size: .82rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .anbk-field .req { color: #dc2626; }
    .anbk-token-wrap { position: relative; }
    .anbk-token-wrap input { width: 100%; border: 1.5px solid #cbd5e1; border-radius: 9px; padding: 11px 44px 11px 14px; font-size: 1.05rem; letter-spacing: .12em; text-transform: uppercase; font-weight: 700; }
    .anbk-token-wrap input:focus { outline: none; border-color: #1565c0; box-shadow: 0 0 0 3px rgba(21,101,192,.15); }
    .anbk-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #64748b; font-size: 1.1rem; cursor: pointer; padding: 6px 8px; }
    .anbk-hint { font-size: .75rem; color: #64748b; margin-top: 6px; }
    .anbk-check { display: flex; gap: 10px; align-items: flex-start; font-size: .83rem; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 9px; padding: 12px 14px; margin-bottom: 16px; cursor: pointer; }
    .anbk-check input { width: 18px; height: 18px; margin-top: 1px; accent-color: #0d47a1; flex-shrink: 0; }
    .anbk-actions { display: flex; gap: 10px; }
    .anbk-btn { flex: 1; border-radius: 9px; font-weight: 700; font-size: .95rem; padding: 12px; cursor: pointer; border: none; transition: filter .15s; }
    .anbk-btn.primary { background: linear-gradient(135deg, #0d47a1, #1565c0); color: #fff; }
    .anbk-btn.primary:hover:not(:disabled) { filter: brightness(1.08); }
    .anbk-btn.primary:disabled { opacity: .55; cursor: not-allowed; }
    .anbk-btn.secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; flex: 0 0 auto; padding: 12px 22px; }
    .anbk-footer { text-align: center; font-size: .72rem; color: #94a3b8; padding: 14px; }
    @media (max-width: 640px) {
        .anbk-ident-grid { grid-template-columns: 1fr 1fr; }
        .anbk-actions { flex-direction: column; }
        .anbk-btn.secondary { flex: 1; }
    }
</style>
