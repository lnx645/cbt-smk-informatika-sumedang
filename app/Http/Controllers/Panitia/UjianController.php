<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\BaseAppController;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\KelengkapanUjianService;
use App\Services\PaketSoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Panitia Ujian — kelola ujian resmi (UTS/UAS/USBK) lintas kelas.
 *
 * Panitia punya akses penuh: paket soal, sesi, ruang, token, dokumen,
 * dan monitoring real-time. Guru tetap kelola ujiannya sendiri.
 */
class UjianController extends BaseAppController
{
    public function __construct(
        private readonly PaketSoalService $paketSoalService,
        private readonly KelengkapanUjianService $kelengkapanService,
    ) {}

    /**
     * Dashboard panitia: daftar ujian resmi + status kelengkapan.
     */
    public function index(Request $request): Response
    {
        $ujians = Ujian::whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->with(['guruKelas.kelas', 'guruKelas.matpel'])
            ->withCount(['paketSoals', 'sesis'])
            ->orderByDesc('id')
            ->paginate(15);

        $ujians->getCollection()->transform(function (Ujian $u) {
            $u->kelengkapan = $this->kelengkapanService->cek($u);
            $u->siap_terbit = $u->kelengkapan === [];

            return $u;
        });

        return Inertia::render('panitia/Ujian/Index', [
            'ujians' => $ujians,
        ]);
    }

    /**
     * Generate paket soal otomatis (1 klik).
     */
    public function generatePaket(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'jumlah_paket' => ['required', 'integer', 'min:2', 'max:10'],
            'soal_per_paket' => ['required', 'integer', 'min:1', 'max:100'],
            'bank_soal_ids' => ['required', 'array', 'min:1'],
            'bank_soal_ids.*' => ['integer'],
        ]);

        try {
            $pakets = $this->paketSoalService->generateOtomatis(
                $ujian,
                $data['jumlah_paket'],
                $data['soal_per_paket'],
                $data['bank_soal_ids'],
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['bank_soal_ids' => $e->getMessage()]);
        }

        return redirect()->back()->with(
            'success',
            count($pakets).' paket soal berhasil dibuat ('.collect($pakets)->pluck('kode')->join(', ').').'
        );
    }

    /**
     * Monitoring real-time: status pengerjaan per sesi.
     */
    public function monitoring(Request $request, Ujian $ujian): Response
    {
        $ujian->load(['sesis.ruang', 'paketSoals']);

        $sesiId = $request->integer('sesi_id') ?: null;

        $query = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->with(['siswa', 'paketSoal', 'sesi'])
            ->orderBy('siswa_nisn');

        if ($sesiId) {
            $query->where('sesi_ujian_id', $sesiId);
        }

        $pengerjaans = $query->get()->map(fn (UjianPengerjaan $p) => [
            'nisn' => $p->siswa_nisn,
            'nama' => $p->siswa?->nama_lengkap ?? $p->siswa_nisn,
            'paket' => $p->paketSoal?->kode,
            'sesi' => $p->sesi?->nama,
            'status' => $p->status,
            'mulai_at' => $p->mulai_at?->toDateTimeString(),
            'batas_at' => $p->batas_at?->toDateTimeString(),
            'jumlah_pelanggaran' => $p->jumlah_pelanggaran,
            'nilai_total' => $p->nilai_total,
            'is_remedial' => $p->is_remedial,
        ]);

        $ringkasan = [
            'total' => $pengerjaans->count(),
            'mengerjakan' => $pengerjaans->where('status', 'berlangsung')->count(),
            'selesai' => $pengerjaans->whereIn('status', ['selesai', 'auto_submit'])->count(),
            'pelanggaran' => $pengerjaans->where('jumlah_pelanggaran', '>', 0)->count(),
        ];

        return Inertia::render('panitia/Ujian/Monitoring', [
            'ujian' => $ujian->only(['id', 'judul', 'kategori', 'durasi_menit']),
            'sesis' => $ujian->sesis->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama]),
            'sesi_id' => $sesiId,
            'ringkasan' => $ringkasan,
            'pengerjaans' => $pengerjaans,
        ]);
    }

    /**
     * Reset attempt siswa bermasalah (dari monitoring).
     */
    public function resetPengerjaan(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan): RedirectResponse
    {
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        $pengerjaan->jawabans()->delete();
        $pengerjaan->delete();

        return redirect()->back()->with('success', 'Pengerjaan siswa direset. Siswa bisa mulai ulang.');
    }
}
