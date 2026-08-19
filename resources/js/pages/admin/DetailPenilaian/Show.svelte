<script lang="ts">
    import {
        Badge,
        Button,
        Card,
        CardBody,
        Input,
        FormGroup,
        Label,
    } from '@sveltestrap/sveltestrap';
    import PageHeader from '@/components/PageHeader.svelte';
    import Avatar from '@/components/Avatar.svelte';
    import DetailPenilaianController from '@/actions/App/Http/Controllers/Admin/DetailPenilaianController';
    import { useForm, router } from '@inertiajs/svelte';

    interface Penilaian {
        id: number;
        nama: string;
        tipe: string;
        nilai_maks: number;
        bobot: number;
        aktif: boolean;
    }

    interface GuruKelasInfo {
        id: number;
        kelas: string | null;
        matpel: string | null;
    }

    interface Siswa {
        nisn: string;
        nama_lengkap: string;
    }

    interface Detail {
        id: number;
        nilai: number;
        keterangan: string | null;
    }

    interface Props {
        penilaian: Penilaian;
        guruKelas: GuruKelasInfo;
        siswa: Siswa;
        detail: Detail | null;
    }

    let { penilaian, guruKelas, siswa, detail }: Props = $props();

    const form = useForm({
        nilai: detail?.nilai ?? ('' as number | ''),
        keterangan: detail?.keterangan ?? '',
    });

    function predikat(
        nilai: number,
        maks: number,
    ): { huruf: string; kelas: string; label: string } {
        const pct = (nilai / maks) * 100;
        if (pct >= 86) {
            return { huruf: 'A', kelas: 'text-success', label: 'Sangat Baik' };
        }
        if (pct >= 71) {
            return { huruf: 'B', kelas: 'text-primary', label: 'Baik' };
        }
        if (pct >= 56) {
            return { huruf: 'C', kelas: 'text-warning', label: 'Cukup' };
        }
        return { huruf: 'D', kelas: 'text-danger', label: 'Kurang' };
    }

    const predikatSaatIni = (): ReturnType<typeof predikat> | null => {
        if (detail?.nilai === undefined || detail?.nilai === null) {
            return null;
        }
        return predikat(detail.nilai, penilaian.nilai_maks || 100);
    };

    const nilaiSlider = (): number =>
        form.nilai === '' ? 0 : Number(form.nilai);

    const nilaiMaks = () => Number(penilaian?.nilai_maks ?? 0);

    function onSliderInput(e: Event) {
        form.nilai = Number((e.currentTarget as HTMLInputElement).value);
    }

    function onNumberInput(e: Event) {
        const raw = (e.currentTarget as HTMLInputElement).value;
        form.nilai = raw === '' ? '' : Number(raw);
    }

    function backUrl() {
        const url = new URL(
            DetailPenilaianController.filterSiswa({
                penilaian: penilaian.id,
            }).url,
            window.location.origin,
        );
        url.searchParams.set('guru_kelas_id', String(guruKelas.id));
        return url.pathname + url.search;
    }

    function handleSubmit() {
        form.post(
            DetailPenilaianController.storeNilai({
                penilaian: penilaian.id,
                guruKelas: guruKelas.id,
                siswa: siswa.nisn,
            }).url,
            {
                preserveScroll: true,
            },
        );
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Input Nilai Siswa"
        subtitle={`${penilaian?.nama ?? 'Penilaian'} · ${guruKelas?.kelas ?? 'Kelas'} · ${guruKelas?.matpel ?? 'Matpel'}`}
    >
        {#snippet actions()}
            <a
                use:inertia
                href={backUrl()}
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-1"></i>Daftar Siswa
            </a>
        {/snippet}
    </PageHeader>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <Card class="border rounded-1 shadow-none h-100">
                <CardBody class="p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <Avatar name={siswa?.nama_lengkap ?? ''} size={52} />
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">
                                {siswa?.nama_lengkap}
                            </div>
                            <div class="text-muted small">NISN: {siswa?.nisn}</div>
                        </div>
                    </div>
                    <div class="small text-muted">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Penilaian</span>
                            <span class="fw-semibold text-body">{penilaian?.nama}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Tipe</span>
                            <span class="fw-semibold text-body">{penilaian?.tipe}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Nilai Maksimum</span>
                            <span class="fw-semibold text-body">{penilaian?.nilai_maks}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Status</span>
                            <span>
                                {#if penilaian?.aktif}
                                    <Badge color="success" pill>Aktif</Badge>
                                {:else}
                                    <Badge color="secondary" pill>Nonaktif</Badge>
                                {/if}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span>Nilai Saat Ini</span>
                            <span class="text-end">
                                {#if detail?.nilai !== null && detail?.nilai !== undefined}
                                    {@const p = predikatSaatIni()}
                                    <span class="fw-bold text-success">
                                        {detail.nilai} / {penilaian?.nilai_maks}
                                    </span>
                                    {#if p}
                                        <div>
                                            <span class="small {p.kelas}">
                                                Predikat {p.huruf} · {p.label}
                                            </span>
                                        </div>
                                    {/if}
                                {:else}
                                    <span class="text-muted">Belum dinilai</span>
                                {/if}
                            </span>
                        </div>
                    </div>
                </CardBody>
            </Card>
        </div>

        <div class="col-12 col-lg-8">
            <Card class="border rounded-1 shadow-none h-100">
                <CardBody class="p-4">
                    <div class="fw-semibold mb-1">
                        <i class="bi bi-pencil-square me-1 text-primary"></i>
                        Isi Nilai
                    </div>
                    <p class="text-muted small mb-4">
                        Nilai maksimal {penilaian?.nilai_maks} — geser slider atau ketik
                        langsung.
                    </p>

                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            handleSubmit();
                        }}
                        novalidate
                    >
                        <FormGroup>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <Label for="nilai" class="fw-semibold mb-0">Nilai</Label>
                                <span class="fw-bold fs-5 {form.errors.nilai ? 'text-danger' : 'text-primary'}">
                                    {form.nilai === '' ? '—' : form.nilai}
                                    <span class="text-muted fs-6 fw-normal">
                                        / {penilaian?.nilai_maks}
                                    </span>
                                </span>
                            </div>
                            <input
                                id="nilai-range"
                                type="range"
                                class="form-range"
                                min="0"
                                max={nilaiMaks()}
                                step="0.1"
                                value={nilaiSlider()}
                                oninput={onSliderInput}
                                disabled={form.processing}
                            />
                            <Input
                                id="nilai"
                                type="number"
                                min="0"
                                max={penilaian?.nilai_maks}
                                step="0.1"
                                class={form.errors.nilai ? 'is-invalid' : ''}
                                value={form.nilai}
                                oninput={onNumberInput}
                                disabled={form.processing}
                                placeholder="Isi nilai…"
                            />
                            {#if form.errors.nilai}
                                <small class="text-danger d-block mt-1">
                                    {form.errors.nilai}
                                </small>
                            {/if}
                        </FormGroup>

                        <FormGroup>
                            <Label for="keterangan" class="fw-semibold">
                                Keterangan <span class="text-muted fw-normal">(opsional)</span>
                            </Label>
                            <Input
                                id="keterangan"
                                type="text"
                                value={form.keterangan}
                                oninput={(e) =>
                                    (form.keterangan = (
                                        e.currentTarget as HTMLInputElement
                                    ).value)}
                                placeholder="Misal: Remidi, tidak ikut ulangan…"
                                disabled={form.processing}
                            />
                            {#if form.errors.keterangan}
                                <small class="text-danger d-block mt-1">
                                    {form.errors.keterangan}
                                </small>
                            {/if}
                        </FormGroup>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-4">
                            <Button
                                color="secondary"
                                outline
                                size="sm"
                                onclick={() => router.visit(backUrl())}
                                disabled={form.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                color="primary"
                                size="sm"
                                type="submit"
                                disabled={form.processing}
                            >
                                {#if form.processing}
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                {:else}
                                    <i class="bi bi-check-lg me-1"></i>
                                {/if}
                                {detail?.nilai !== null && detail?.nilai !== undefined
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Nilai'}
                            </Button>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    </div>
</div>
