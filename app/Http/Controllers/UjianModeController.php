<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman-halaman mode ujian terpisah (tanpa dashboard).
 * Siswa login via /ujian/login, langsung ke daftar ujian.
 */
class UjianModeController extends Controller
{
    /**
     * Daftar ujian yang tersedia untuk siswa (mode ujian).
     */
    public function daftar(Request $request): Response
    {
        $nisn = $request->session()->get('ujian_nisn');
        abort_unless($nisn, 403);
        $siswa = \App\Models\Siswa::where('nisn', $nisn)->first();
        abort_unless($siswa, 403);

        $kelasIds = \App\Models\SiswaKelas::where('siswa_nisn', $nisn)
            ->where('active', true)
            ->pluck('kelas_id');

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'sesis'])
            ->withCount('soals')
            ->where('status', 'terbit')
            ->whereIn('kategori', Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas', fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->orderByDesc('created_at')
            ->get();

        $pengerjaans = UjianPengerjaan::query()
            ->where('siswa_nisn', $nisn)
            ->whereIn('ujian_id', $ujians->pluck('id'))
            ->get()
            ->groupBy('ujian_id');

        return Inertia::render('ujian/Daftar', [
            'siswa' => [
                'nama' => $siswa->nama_lengkap,
                'nisn' => $siswa->nisn,
            ],
            'ujians' => $ujians->map(function (Ujian $u) use ($pengerjaans): array {
                $riwayat = $pengerjaans->get($u->id, collect());
                $selesai = $riwayat->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi']);
                $berlangsung = $riwayat->firstWhere('status', 'berlangsung');
                // Token per sesi: butuh token jika ada sesi dengan token
                $butuhToken = $u->sesis->contains(fn ($s) => ! empty($s->token));

                return [
                    'id' => $u->id,
                    'judul' => $u->judul,
                    'kategori' => $u->kategori,
                    'matpel' => $u->guruKelas?->matpel?->name,
                    'durasi_menit' => $u->durasi_menit,
                    'jumlah_soal' => $u->soals_count,
                    'sedang_berlangsung' => $u->sedangBerlangsung(),
                    'butuh_token' => $butuhToken,
                    'sudah_selesai' => $selesai->isNotEmpty(),
                    'sedang_dikerjakan' => $berlangsung !== null,
                ];
            }),
        ]);
    }
}
