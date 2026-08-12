<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import {
        Button,
        Card,
        CardBody,
        Col,
        FormFeedback,
        FormGroup,
        Input,
        InputGroup,
        InputGroupText,
        Label,
        Row,
    } from '@sveltestrap/sveltestrap';
    import { toast } from 'svelte-sonner';
    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    let passwordVisible = $state(false);
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });
    function submit(e: any) {
        e.preventDefault();
        form.post('/login', {
            onError(e) {
                toast.error(e.email || e.password);
            },
        });
    }
</script>

<div class="login-page min-vh-100 d-flex align-items-center">
    <Card
        class="login-card w-100 m-auto"
        style="max-width: 940px; border: none;"
    >
        <Row class="g-0">
            <!-- Bagian Kiri (Biru) -->
            <Col
                lg={5}
                class="login-brand d-none d-lg-flex flex-column justify-content-between p-5 text-white"
            >
                <div class="d-flex align-items-center gap-3">
                    <img
                        class="login-logo"
                        src="https://smkifsu.sch.id/assets/img/logo.png"
                        alt="Lambang Tut Wuri Handayani"
                    />
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="lh-1 fw-bold fs-5">{appName}</span>
                        </div>
                        <div class="brand-title fs-6 fw-normal opacity-75">
                            CBT Ujian Berbasis Komputer
                        </div>
                    </div>
                </div>

                <div
                    class="d-flex flex-column align-items-center text-center my-4"
                >
                    <img
                        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS8lyCvH94bzd4Zo92Pep1aym-_ipv98jkPYoAFv0rzsg&s=10"
                        alt="Foto Ki Hajar Dewantara"
                        class="img-fluid user-select-none rounded-circle mb-4 border border-4 border-warning shadow"
                        style="width: 140px; height: 140px; object-fit: cover;pointer-events:none;"
                    />
                    <p class="quote-text mb-0 fw-medium">
                        "Ing Ngarso Sung Tulodo,<br />Ing Madya Mangun Karso,<br
                        />Tut Wuri Handayani"
                    </p>
                </div>

                <div class="text-center small opacity-75"></div>
            </Col>

            <Col lg={7} class="bg-white">
                <CardBody class="p-4 p-sm-5">
                    <div class="d-lg-none mb-4">
                        <h2 class="h4 mb-0">{appName}</h2>
                        <span class="brand-kicker text-uppercase"
                            >Ujian Berbasis Komputer</span
                        >
                    </div>

                    <h2 class="h4 mb-1 fw-bold">Masuk</h2>
                    <p class="text-muted mb-4 small">
                        Silakan masuk untuk melanjutkan ke halaman ujian.
                    </p>

                    <form onsubmit={submit}>
                        <FormGroup class="mb-3">
                            <Label
                                for="username"
                                class="text-dark small fw-semibold"
                                >NISN / Email</Label
                            >
                            <InputGroup>
                                <InputGroupText>
                                    <i
                                        class="bi bi-person login-icon text-muted"
                                    ></i>
                                </InputGroupText>
                                <Input
                                    bind:value={form.email}
                                    invalid={!!form.errors.email}
                                    id="username"
                                    placeholder="Masukkan NISN atau email"
                                />
                            </InputGroup>
                        </FormGroup>

                        <FormGroup class="mb-3">
                            <Label
                                for="password"
                                class="text-dark small fw-semibold"
                                >Kata Sandi</Label
                            >
                            <InputGroup>
                                <InputGroupText>
                                    <i class="bi bi-lock login-icon text-muted"
                                    ></i>
                                </InputGroupText>
                                <Input
                                    bind:value={form.password}
                                    invalid={!!form.errors.password}
                                    id="password"
                                    type={passwordVisible ? 'text' : 'password'}
                                    placeholder="Masukkan kata sandi"
                                />
                                <button
                                    type="button"
                                    class="btn border bg-transparent d-flex align-items-center pe-3"
                                    style="color: #64748b; border-color: #dee2e6;"
                                    onclick={() =>
                                        (passwordVisible = !passwordVisible)}
                                    aria-label={passwordVisible
                                        ? 'Sembunyikan kata sandi'
                                        : 'Tampilkan kata sandi'}
                                >
                                    {#if passwordVisible}
                                        <i class="bi bi-eye-slash login-icon"
                                        ></i>
                                    {:else}
                                        <i class="bi bi-eye login-icon"></i>
                                    {/if}
                                </button>
                            </InputGroup>
                        </FormGroup>

                        <Row class="align-items-center mb-4 mt-2">
                            <Col xs={7}>
                                <div
                                    class="remember-check ms-1 form-check form-switch mb-0"
                                >
                                    <input
                                        id="remember"
                                        type="checkbox"
                                        class="form-check-input"
                                        bind:checked={form.remember}
                                    />
                                    <label
                                        for="remember"
                                        class="form-check-label text-muted small user-select-none"
                                    >
                                        Ingat saya
                                    </label>
                                </div>
                            </Col>
                            <Col xs={5} class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-link p-0 small fw-semibold text-decoration-none"
                                    style="color: #006fa5"
                                    >Lupa kata sandi?</button
                                >
                            </Col>
                        </Row>
                        <Button
                            color="primary"
                            size="lg"
                            class="w-100 d-flex align-items-center justify-content-center gap-2"
                            onclick={submit}
                        >
                            <span class="fs-6">Masuk</span>
                            <i class="bi bi-arrow-right login-icon"></i>
                        </Button>
                    </form>

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
                        <span class="small fw-semibold"
                            >Masuk dengan Google</span
                        >
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
    /* CSS BACKGROUND SEKOLAH */
    .login-page {
        /* Silakan ganti URL ini dengan foto lingkungan sekolah Anda */
        background-image: url('https://smkifsu.sch.id/assets/img/foto1.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        /* Lapisan gelap/overlay agar card tetap terbaca jelas */
        box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.4);
    }

    :global(.login-card) {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    :global(.login-brand) {
        position: relative;
        background: linear-gradient(160deg, #2b7bc4 0%, #005a85 100%);
    }

    .login-logo {
        width: 48px;
        height: 48px;
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
    }

    .quote-text {
        font-size: 1rem;
        line-height: 1.6;
    }

    .login-icon {
        font-size: 1.1rem;
    }

    .remember-check .form-check-input {
        width: 2.2em;
        height: 1.15em;
        cursor: pointer;
    }

    .remember-check .form-check-input:checked {
        background-color: #0091d4;
        border-color: #0091d4;
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
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .google-btn:hover {
        background-color: #f8fafc;
        border-color: #94a3b8;
    }

    .google-icon {
        font-size: 1.1rem;
    }
</style>
