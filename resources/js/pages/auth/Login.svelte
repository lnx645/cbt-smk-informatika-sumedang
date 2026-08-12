<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import {
        Button,
        Card,
        CardBody,
        Col,
        FormGroup,
        Input,
        InputGroup,
        InputGroupText,
        Label,
        Row,
    } from '@sveltestrap/sveltestrap';

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    let passwordVisible = $state(false);
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const hasErrors = $derived(Object.keys(form.errors).length > 0);

    function submit(e: any) {
        e.preventDefault();
        form.post('/login');
    }
</script>

<div class="login-page min-vh-100">
    <Card
        class="login-card rounded-top-0 w-100 m-auto"
        style="max-width: 940px; border: none"
    >
        <Row class="g-0">
            <Col
                lg={5}
                class="login-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white"
            >
                <div class="d-flex align-items-center gap-3">
                    <img
                        class="login-logo"
                        src="https://smkifsu.sch.id/assets/img/logo.png"
                        alt="Lambang Tut Wuri Handayani Kemendikbud"
                    />
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="lh-1 fw-bold">{appName}</span>
                            <span class="brand-kicker">CBT</span>
                        </div>
                        <div class="brand-title">Ujian Berbasis Komputer</div>
                    </div>
                </div>

                <div
                    class="d-flex flex-column align-items-center text-center my-4"
                >
                    <img
                        src="https://bpmpriau.kemendikdasmen.go.id/wp-content/uploads/2017/10/70f22-ki-hajar-dewantara.jpg"
                        alt="Foto Ki Hajar Dewantara"
                        class="img-fluid object-fit-cover rounded-circle mb-4"
                        style="max-width: 150px; height: 150px;"
                    />
                    <p class="quote-text mb-0">
                        <em
                            >"Ing Ngarso Sung Tulodo,<br />Ing Madya Mangun
                            Karso,<br />Tut Wuri Handayani."</em
                        >
                    </p>
                    <p class="quote-author mt-2">— Ki Hajar Dewantara</p>
                </div>

                <div class="d-flex align-items-center gap-2 small opacity-75">
                    <span class="ms-2"
                        >Citra Lambang Tut Wuri Handayani — Kemendikbud</span
                    >
                </div>
            </Col>

            <Col lg={7}>
                <CardBody class="p-4 p-sm-5">
                    <div class="d-lg-none mb-4">
                        <h2 class="h4 mb-0">{appName}</h2>
                        <span class="brand-kicker text-uppercase"
                            >Ujian Berbasis Komputer</span
                        >
                    </div>

                    <h2 class="h4 mb-1">Masuk</h2>
                    <p class="text-muted mb-4">
                        Silakan masuk untuk melanjutkan ke halaman ujian.
                    </p>

                    <FormGroup class="mb-3">
                        <Label for="username" class="text-dark fw-semibold fs-6"
                            >NISN / Email</Label
                        >
                        <InputGroup>
                            <InputGroupText>
                                <i class="bi bi-person login-icon"></i>
                            </InputGroupText>
                            <Input
                                id="username"
                                placeholder="Masukkan NISN atau email"
                            />
                        </InputGroup>
                    </FormGroup>

                    <FormGroup class="mb-3">
                        <Label for="password" class="text-dark fw-semibold fs-6"
                            >Kata Sandi</Label
                        >
                        <InputGroup>
                            <InputGroupText>
                                <i class="bi bi-lock login-icon"></i>
                            </InputGroupText>
                            <Input
                                id="password"
                                type={passwordVisible ? 'text' : 'password'}
                                placeholder="Masukkan kata sandi"
                            />
                            <button
                                type="button"
                                class="btn btn-sm border-0 d-flex align-items-center"
                                style="background: transparent; color: #64748b"
                                onclick={() =>
                                    (passwordVisible = !passwordVisible)}
                                aria-label={passwordVisible
                                    ? 'Sembunyikan kata sandi'
                                    : 'Tampilkan kata sandi'}
                            >
                                {#if passwordVisible}
                                    <i class="bi bi-eye-slash login-icon"></i>
                                {:else}
                                    <i class="bi bi-eye login-icon"></i>
                                {/if}
                            </button>
                        </InputGroup>
                    </FormGroup>

                    <Row class="align-items-center mb-4">
                        <Col md={7}>
                            <div
                                class="remember-check ms-lg-3 ms-3 form-check form-switch mb-0"
                            >
                                <input
                                    id="remember"
                                    type="checkbox"
                                    class="form-check-input"
                                    bind:checked={form.remember}
                                />
                                <label
                                    for="remember"
                                    class="form-check-label text-muted small"
                                >
                                    Ingat saya
                                </label>
                            </div>
                        </Col>
                        <Col md={5} class="text-md-end mt-3 mt-md-0">
                            <button
                                type="button"
                                class="btn btn-link p-0 small fw-semibold text-decoration-none"
                                style="color: #006fa5">Lupa kata sandi?</button
                            >
                        </Col>
                    </Row>

                    <Button
                        color="primary"
                        size="lg"
                        class="w-100 d-flex align-items-center justify-content-center gap-2"
                        onclick={submit}
                    >
                        <span>Masuk</span>
                        <i class="bi bi-arrow-right login-icon"></i>
                    </Button>

                    <div class="d-flex align-items-center gap-3 my-4">
                        <span class="divider-line"></span>
                        <span class="text-muted small text-nowrap">atau</span>
                        <span class="divider-line"></span>
                    </div>

                    <a
                        href="/auth/google/redirect"
                        class="google-btn btn w-100 d-flex align-items-center justify-content-center gap-2 py-2"
                    >
                        <i
                            class="bi bi-google google-icon"
                            style="color: #EA4335;"
                        ></i>
                        <span>Masuk dengan Google</span>
                    </a>

                    <p class="text-center text-muted small mt-4 mb-0">
                        Butuh bantuan? Hubungi operator sekolah Anda.
                    </p>
                </CardBody>
            </Col>
        </Row>
    </Card>
</div>

<style>
    .login-page {
        background: linear-gradient(
            160deg,
            #e6f4fb 0%,
            #ffffff 55%,
            #f3faff 100%
        );
    }

    :global(.login-card) {
        border-radius: 20px;
        overflow: hidden;
        box-shadow:
            0 8px 20px rgba(0, 111, 165, 0.08),
            0 24px 56px rgba(0, 111, 165, 0.16);
    }

    :global(.login-brand) {
        position: relative;
        background: linear-gradient(
            160deg,
            #0091d4 0%,
            #006fa5 62%,
            #005a85 100%
        );
    }

    :global(.login-brand)::before,
    :global(.login-brand)::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    :global(.login-brand)::before {
        width: 280px;
        height: 280px;
        right: -110px;
        top: -90px;
        background: radial-gradient(
            circle,
            rgba(253, 212, 6, 0.28) 0%,
            rgba(253, 212, 6, 0) 70%
        );
    }

    :global(.login-brand)::after {
        width: 320px;
        height: 320px;
        left: -140px;
        bottom: -120px;
        background: radial-gradient(
            circle,
            rgba(255, 255, 255, 0.18) 0%,
            rgba(255, 255, 255, 0) 70%
        );
    }

    .login-logo {
        width: 56px;
        height: 56px;
        object-fit: contain;
        border-radius: 50%;
    }

    .brand-kicker {
        display: inline-block;
        background-color: #fdd406;
        color: #006fa5;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        text-transform: uppercase;
    }

    .brand-title {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: 0.02em;
    }

    .login-medallion {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: conic-gradient(
            #fdd406 0deg 120deg,
            #0091d4 120deg 240deg,
            #ffffff 240deg 360deg
        );
        box-shadow:
            0 0 0 8px rgba(255, 255, 255, 0.22),
            0 0 0 11px rgba(253, 212, 6, 0.9);
    }

    .quote-text {
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .quote-author {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .login-icon {
        font-size: 1.1rem; /* Disesuaikan untuk icon font */
    }

    .remember-check .form-check-input {
        width: 2.2em;
        height: 1.15em;
        cursor: pointer;
        border-color: #cbd5e1;
        background-color: #f1f5f9;
    }

    .remember-check .form-check-input:checked {
        background-color: #0091d4;
        border-color: #0091d4;
    }

    .remember-check .form-check-input:focus {
        box-shadow: 0 0 0 3px rgba(0, 145, 212, 0.18);
    }

    .remember-check .form-check-label {
        cursor: pointer;
        user-select: none;
    }

    .divider-line {
        flex: 1;
        height: 1px;
        background-color: #e2e8f0;
    }

    .google-btn {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 600;
        border-radius: 8px;
        transition:
            border-color 0.15s ease,
            background-color 0.15s ease,
            box-shadow 0.15s ease;
    }

    .google-btn:hover {
        border-color: #94a3b8;
        background-color: #f8fafc;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }

    .google-icon {
        font-size: 1.2rem; /* Disesuaikan untuk icon font */
    }
</style>
