<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\BaseAppController;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\KelengkapanUjianService;
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
            'id' => $p->id,
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

    /**
     * Detail pengaturan panitia: sesi, ruang, jadwal, token, tata tertib, KKM.
     * Alur sekolah beneran: guru serahkan soal → panitia atur & terbitkan.
     */
    public function show(Request $request, Ujian $ujian): Response
    {
        $ujian->load(['paketSoals.soals', 'sesis.ruang', 'guruKelas.kelas', 'guruKelas.matpel', 'guru']);

        return Inertia::render('panitia/Ujian/Show', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'status' => $ujian->status,
                'durasi_menit' => $ujian->durasi_menit,
                'token' => $ujian->token,
                'tata_tertib' => $ujian->tata_tertib,
                'kkm' => $ujian->kkm,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'guru' => $ujian->guru?->nama_lengkap,
                'paket_soals' => $ujian->paketSoals->map(fn ($p) => [
                    'id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama,
                    'jumlah_soal' => $p->soals->count(),
                ]),
                'sesis' => $ujian->sesis->map(fn ($s) => [
                    'id' => $s->id, 'nama' => $s->nama,
                    'waktu_mulai' => $s->waktu_mulai?->format('Y-m-d\TH:i'),
                    'waktu_selesai' => $s->waktu_selesai?->format('Y-m-d\TH:i'),
                    'ruang_ujian_id' => $s->ruang_ujian_id,
                    'ruang' => $s->ruang?->nama,
                    'kapasitas' => $s->kapasitas,
                ]),
            ],
            'ruangs' => \App\Models\RuangUjian::orderBy('nama')->get(['id', 'nama', 'kapasitas']),
            'kelengkapan' => $this->kelengkapanService->cek($ujian),
        ]);
    }

    /**
     * Panitia simpan sesi & ruang (mengganti semua).
     */
    public function storeSesi(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'sesis' => ['required', 'array', 'min:1'],
            'sesis.*.nama' => ['required', 'string', 'max:100'],
            'sesis.*.waktu_mulai' => ['required', 'date'],
            'sesis.*.waktu_selesai' => ['required', 'date', 'after:sesis.*.waktu_mulai'],
            'sesis.*.ruang_ujian_id' => ['nullable', 'integer', 'exists:ruang_ujians,id'],
            'sesis.*.kapasitas' => ['nullable', 'integer', 'min:1'],
        ]);

        $ujian->sesis()->delete();

        foreach (array_values($data['sesis']) as $i => $s) {
            $ujian->sesis()->create([
                'nama' => $s['nama'],
                'urutan' => $i + 1,
                'waktu_mulai' => $s['waktu_mulai'],
                'waktu_selesai' => $s['waktu_selesai'],
                'ruang_ujian_id' => $s['ruang_ujian_id'] ?? null,
                'kapasitas' => $s['kapasitas'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal sesi tersimpan.');
    }

    /**
     * Panitia simpan token, tata tertib, KKM.
     */
    public function storePengaturan(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'tata_tertib' => ['required', 'string', 'min:10'],
            'kkm' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $ujian->update([
            'token' => $ujian->token ?? app(\App\Services\UjianService::class)->generateToken(),
            'token_released_at' => now(),
            'tata_tertib' => $data['tata_tertib'],
            'kkm' => $data['kkm'],
        ]);

        return redirect()->back()->with('success', 'Token, tata tertib & KKM tersimpan.');
    }

    /**
     * Panitia terbitkan ujian resmi.
     */
    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        $masalah = $this->kelengkapanService->cek($ujian);
        if ($masalah !== []) {
            return redirect()->back()->withErrors(['kelengkapan' => 'Belum bisa diterbitkan: '.implode(' ', $masalah)]);
        }

        $ujian->update(['status' => 'terbit']);
        app(\App\Services\NotifikasiService::class)->jadwalUjian($ujian->fresh());

        return redirect()->route('app.panitia.ujian.index')->with('success', 'Ujian diterbitkan!');
    }

    /**
     * Panitia kembalikan ke guru (revisi).
     */
    public function kembalikan(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);

        $ujian->update(['status' => 'draft']);

        return redirect()->route('app.panitia.ujian.index')->with(
            'success',
            'Ujian dikembalikan ke guru untuk direvisi.'.($data['catatan'] ? ' Catatan: '.$data['catatan'] : '')
        );
    }
}
