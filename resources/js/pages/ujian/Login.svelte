<script lang="ts">
    import { useForm } from '@inertiajs/svelte';

    const form = useForm({
        nisn: '',
        password: '',
    });

    function submit() {
        form.post('/ujian/login');
    }
</script>

<svelte:head>
    <title>Login Ujian — CBT SMK Informatika Sumedang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</svelte:head>

<div class="anbk-login">
    <!-- Header ala ANBK -->
    <header class="anbk-header">
        <div class="anbk-header-inner">
            <div class="anbk-brand">
                <div class="anbk-logo">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="anbk-brand-text">
                    <div class="anbk-title">UJIAN BERBASIS KOMPUTER</div>
                    <div class="anbk-subtitle">SMK Informatika Sumedang</div>
                </div>
            </div>
            <div class="anbk-header-right d-none d-md-block">
                <i class="bi bi-shield-lock-fill me-1"></i> Sistem Ujian Resmi
            </div>
        </div>
    </header>

    <!-- Konten -->
    <main class="anbk-main">
        <div class="anbk-card">
            <div class="anbk-card-head">
                <h1>Login Peserta Ujian</h1>
                <p>Masuk menggunakan NISN dan password yang tercetak pada kartu peserta.</p>
            </div>

            {#if form.errors.nisn}
                <div class="anbk-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{form.errors.nisn}
                </div>
            {/if}

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    submit();
                }}
            >
                <div class="anbk-field">
                    <label for="nisn">Nomor Induk Siswa Nasional (NISN)</label>
                    <div class="anbk-input-wrap">
                        <i class="bi bi-person-badge"></i>
                        <input
                            id="nisn"
                            type="text"
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="Contoh: 1234567890"
                            bind:value={form.nisn}
                            required
                            autocomplete="username"
                        />
                    </div>
                </div>

                <div class="anbk-field">
                    <label for="password">Password Ujian</label>
                    <div class="anbk-input-wrap">
                        <i class="bi bi-key-fill"></i>
                        <input
                            id="password"
                            type="password"
                            placeholder="6 karakter dari kartu peserta"
                            maxlength="6"
                            bind:value={form.password}
                            required
                            autocomplete="current-password"
                            style="text-transform: uppercase;"
                        />
                    </div>
                    <div class="anbk-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Password tercetak pada kartu peserta ujian. Token sesi akan diminta oleh pengawas di ruang ujian.
                    </div>
                </div>

                <button type="submit" class="anbk-btn" disabled={form.processing}>
                    {#if form.processing}
                        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        Memeriksa…
                    {:else}
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                    {/if}
                </button>
            </form>

            <div class="anbk-card-foot">
                <div class="anbk-step"><span>1</span> Login dengan NISN + password</div>
                <div class="anbk-step"><span>2</span> Pilih ujian yang tersedia</div>
                <div class="anbk-step"><span>3</span> Masukkan token dari pengawas</div>
            </div>
        </div>
    </main>

    <footer class="anbk-footer">
        <span><i class="bi bi-building me-1"></i> SMK Informatika Sumedang</span>
        <span class="d-none d-sm-inline"><i class="bi bi-clock me-1"></i> Pastikan perangkat &amp; koneksi stabil sebelum mulai</span>
    </footer>
</div>

<style>
    :global(body) { margin: 0 !important; padding: 0 !important; }
    :global(html) { margin: 0 !important; padding: 0 !important; }
    .anbk-login {
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
        max-width: 960px;
        margin: 0 auto;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .anbk-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .anbk-logo {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .anbk-title {
        font-weight: 800;
        letter-spacing: 0.06em;
        font-size: 1rem;
    }
    .anbk-subtitle {
        font-size: 0.8rem;
        opacity: 0.85;
    }
    .anbk-header-right {
        font-size: 0.85rem;
        opacity: 0.9;
    }
    .anbk-main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px 16px;
    }
    .anbk-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(13, 71, 161, 0.12);
        width: 100%;
        max-width: 430px;
        overflow: hidden;
        border-top: 5px solid #0d47a1;
    }
    .anbk-card-head {
        padding: 24px 26px 6px;
    }
    .anbk-card-head h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0d47a1;
        margin: 0 0 6px;
    }
    .anbk-card-head p {
        font-size: 0.85rem;
        color: #5f6b7a;
        margin: 0;
    }
    .anbk-alert {
        margin: 14px 26px 0;
        background: #fdecea;
        border: 1px solid #f5c6cb;
        color: #a12622;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.85rem;
    }
    .anbk-card form {
        padding: 18px 26px 6px;
    }
    .anbk-field {
        margin-bottom: 16px;
    }
    .anbk-field label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }
    .anbk-input-wrap {
        position: relative;
    }
    .anbk-input-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #0d47a1;
        font-size: 1rem;
    }
    .anbk-input-wrap input {
        width: 100%;
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        padding: 11px 12px 11px 38px;
        font-size: 1rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .anbk-input-wrap input:focus {
        outline: none;
        border-color: #1565c0;
        box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.15);
    }
    .anbk-hint {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 6px;
    }
    .anbk-btn {
        width: 100%;
        border: none;
        border-radius: 9px;
        background: linear-gradient(135deg, #0d47a1, #1565c0);
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        padding: 12px;
        margin-top: 4px;
        cursor: pointer;
        transition: filter 0.15s, transform 0.05s;
    }
    .anbk-btn:hover:not(:disabled) {
        filter: brightness(1.08);
    }
    .anbk-btn:active:not(:disabled) {
        transform: scale(0.99);
    }
    .anbk-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .anbk-card-foot {
        display: flex;
        gap: 8px;
        padding: 18px 26px 22px;
    }
    .anbk-step {
        flex: 1;
        font-size: 0.68rem;
        color: #475569;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 8px 6px;
        text-align: center;
        line-height: 1.3;
    }
    .anbk-step span {
        display: inline-flex;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #0d47a1;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        align-items: center;
        justify-content: center;
        margin-right: 2px;
    }
    .anbk-footer {
        padding: 12px 20px;
        font-size: 0.75rem;
        color: #64748b;
        display: flex;
        justify-content: space-between;
        max-width: 960px;
        width: 100%;
        margin: 0 auto;
    }
    @media (max-width: 480px) {
        .anbk-card-head h1 {
            font-size: 1.1rem;
        }
        .anbk-card-foot {
            flex-direction: column;
        }
    }
</style>
