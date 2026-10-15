<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\GuruKelas;
use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Services\KelengkapanUjianService;
use App\Services\PaketSoalService;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Wizard pembuatan ujian terpandu.
 *
 * - Mode santai (kuis/uh): 3 langkah (info → soal → review).
 * - Mode resmi (uts/uas/usbk): 5 langkah (info → paket → sesi → token → review).
 *
 * Ujian dibuat sebagai draft di langkah 1, tiap langkah memperbarui draft.
 */
class UjianWizardController extends BaseAppController
{
    public function __construct(
        private readonly UjianService $ujianService,
        private readonly PaketSoalService $paketSoalService,
        private readonly KelengkapanUjianService $kelengkapanService,
    ) {}

    /**
     * Langkah 1: pilih mode & info dasar.
     */
    public function create(Request $request): Response
    {
        $guruId = $request->user()->guru?->id;

        $penugasan = GuruKelas::where('guru_id', $guruId)
            ->where('aktif', true)
            ->with(['kelas', 'matpel'])
            ->get()
            ->map(fn ($gk) => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? '-').' — '.($gk->matpel?->name ?? '-'),
            ]);

        return Inertia::render('guru/Ujian/Wizard', [
            'penugasan' => $penugasan,
            'mode' => $request->query('mode', 'santai'),
        ]);
    }

    /**
     * Simpan langkah 1: buat draft ujian.
     */
    public function storeInfo(Request $request): RedirectResponse
    {
        $guruId = $request->user()->guru?->id;

        $data = $request->validate([
            'mode' => ['required', 'in:santai,resmi'],
            'guru_kelas_id' => ['required', 'integer'],
            'kategori' => ['required', 'string'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'durasi_menit' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        $penugasan = GuruKelas::where('id', $data['guru_kelas_id'])
            ->where('guru_id', $guruId)->where('aktif', true)->firstOrFail();

        // Validasi kategori sesuai mode.
        $kategoriBesar = in_array($data['kategori'], Ujian::KATEGORI_BESAR, true);
        if ($data['mode'] === 'santai' && $kategoriBesar) {
            $data['kategori'] = 'kuis';
        }
        if ($data['mode'] === 'resmi' && ! $kategoriBesar) {
            $data['kategori'] = 'uts';
        }

        $defaults = $this->ujianService->defaultKategori($data['kategori']);

        $ujian = Ujian::create(array_merge($defaults, [
            'guru_kelas_id' => $penugasan->id,
            'guru_id' => $guruId,
            'kategori' => $data['kategori'],
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'durasi_menit' => $data['durasi_menit'],
            'status' => 'draft',
        ]));

        // Tata tertib default sesuai mode.
        if ($data['mode'] === 'santai') {
            $ujian->update(['tata_tertib' => "1. Kerjakan dengan jujur.\n2. Dilarang bekerja sama.\n3. Perhatikan sisa waktu."]);
        }

        Toast::success('Langkah 1 selesai. Lanjut ke langkah berikutnya.');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 2, 'mode' => $data['mode'],
        ]);
    }

    /**
     * Tampilkan langkah wizard.
     */
    public function langkah(Request $request, Ujian $ujian, int $langkah): Response|RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $mode = $request->query('mode', 'santai');
        $maksLangkah = $mode === 'resmi' ? 5 : 3;

        if ($langkah < 2 || $langkah > $maksLangkah) {
            return Redirect::route('app.guru.ujian.index');
        }

        $ujian->load(['paketSoals.soals', 'sesis.ruang', 'soals']);

        $props = [
            'ujian' => $ujian,
            'mode' => $mode,
            'langkah' => $langkah,
            'maksLangkah' => $maksLangkah,
            'kelengkapan' => $this->kelengkapanService->cek($ujian),
        ];

        // Data tambahan per langkah.
        if ($mode === 'resmi' && $langkah === 2) {
            $props['bankSoalCount'] = \App\Models\BankSoal::where(
                'matpel_id', $ujian->guruKelas?->matpel_id
            )->count();
        }

        if ($mode === 'resmi' && $langkah === 3) {
            $props['ruangs'] = RuangUjian::orderBy('nama')->get(['id', 'nama', 'kapasitas']);
        }

        return Inertia::render('guru/Ujian/WizardLangkah', $props);
    }

    /**
     * Generate paket soal (langkah 2 mode resmi).
     */
    public function generatePaket(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'jumlah_paket' => ['required', 'integer', 'min:2', 'max:10'],
            'soal_per_paket' => ['required', 'integer', 'min:1', 'max:100'],
            'bank_soal_ids' => ['required', 'array', 'min:1'],
            'bank_soal_ids.*' => ['integer'],
        ]);

        try {
            $pakets = $this->paketSoalService->generateOtomatis(
                $ujian, $data['jumlah_paket'], $data['soal_per_paket'], $data['bank_soal_ids']
            );
        } catch (\InvalidArgumentException $e) {
            Toast::error($e->getMessage());

            return Redirect::back();
        }

        Toast::success(count($pakets).' paket soal berhasil dibuat.');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 3, 'mode' => 'resmi',
        ]);
    }

    /**
     * Simpan sesi (langkah 3 mode resmi).
     */
    public function storeSesi(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'sesis' => ['required', 'array', 'min:1'],
            'sesis.*.nama' => ['required', 'string', 'max:100'],
            'sesis.*.waktu_mulai' => ['required', 'date'],
            'sesis.*.waktu_selesai' => ['required', 'date', 'after:sesis.*.waktu_mulai'],
            'sesis.*.ruang_id' => ['nullable', 'integer', 'exists:ruang_ujians,id'],
            'sesis.*.kapasitas' => ['nullable', 'integer', 'min:1'],
        ]);

        foreach ($data['sesis'] as $s) {
            $ujian->sesis()->create([
                'nama' => $s['nama'],
                'waktu_mulai' => $s['waktu_mulai'],
                'waktu_selesai' => $s['waktu_selesai'],
                'ruang_id' => $s['ruang_id'] ?? null,
                'kapasitas' => $s['kapasitas'] ?? null,
            ]);
        }

        Toast::success('Sesi tersimpan.');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 4, 'mode' => 'resmi',
        ]);
    }

    /**
     * Simpan token & tata tertib (langkah 4 mode resmi).
     */
    public function storeToken(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $request->validate([
            'tata_tertib' => ['required', 'string', 'min:10'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $ujian->update([
            'token' => $ujian->token ?? $this->ujianService->generateToken(),
            'token_released_at' => now(),
            'tata_tertib' => $data['tata_tertib'],
            'kkm' => $data['kkm'],
        ]);

        Toast::success('Token & tata tertib tersimpan.');

        return Redirect::route('app.guru.ujian.wizard.langkah', [
            'ujian' => $ujian->id, 'langkah' => 5, 'mode' => 'resmi',
        ]);
    }

    /**
     * Terbitkan dari wizard (langkah terakhir).
     */
    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $masalah = $this->kelengkapanService->cek($ujian);
        if ($masalah !== []) {
            Toast::error('Belum bisa diterbitkan: '.implode(' ', $masalah));

            return Redirect::back();
        }

        $ujian->update(['status' => 'terbit']);
        app(\App\Services\NotifikasiService::class)->jadwalUjian($ujian->fresh());
        Toast::success('Ujian diterbitkan!');

        return Redirect::route('app.guru.ujian.index');
    }
}
