<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\JawabanSiswa;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class HasilUjianController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly UjianService $ujianService)
    {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                if ($request->user()?->role !== 'guru') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Rekap pengerjaan seluruh siswa untuk satu ujian.
     */
    public function index(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Hasil', $this->hasilData($ujian));
    }

    /**
     * Detail satu sesi pengerjaan untuk koreksi esai & lihat pelanggaran.
     */
    public function show(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        $pengerjaan->load(['jawabans.soal.opsi', 'pelanggarans', 'siswa']);

        return Inertia::render('guru/Ujian/Koreksi', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
            ],
            'pengerjaan' => [
                'id' => $pengerjaan->id,
                'siswa' => $pengerjaan->siswa?->nama_lengkap ?? $pengerjaan->siswa_nisn,
                'nisn' => $pengerjaan->siswa_nisn,
                'status' => $pengerjaan->status,
                'nilai_objektif' => $pengerjaan->nilai_objektif,
                'nilai_esai' => $pengerjaan->nilai_esai,
                'nilai_total' => $pengerjaan->nilai_total,
                'jumlah_pelanggaran' => $pengerjaan->jumlah_pelanggaran,
                'submitted_at' => $pengerjaan->submitted_at?->translatedFormat('d M Y H:i'),
            ],
            'jawabans' => $pengerjaan->jawabans->map(fn (JawabanSiswa $j): array => [
                'id' => $j->id,
                'soal_id' => $j->soal_id,
                'tipe' => $j->soal?->tipe,
                'pertanyaan' => $j->soal?->pertanyaan,
                'poin' => $j->soal?->poin,
                'opsi_dipilih' => $j->opsi_dipilih,
                'jawaban_teks' => $j->jawaban_teks,
                'benar' => $j->benar,
                'skor' => $j->skor,
                'opsi' => $j->soal?->opsi->map(fn ($o): array => [
                    'id' => $o->id,
                    'teks' => $o->teks,
                    'benar' => $o->benar,
                ])->values(),
            ])->values(),
            'pelanggarans' => $pengerjaan->pelanggarans->map(fn ($p): array => [
                'jenis' => $p->jenis,
                'terjadi_at' => $p->terjadi_at?->translatedFormat('d M Y H:i:s'),
            ])->values(),
        ]);
    }

    /**
     * Simpan skor esai satu jawaban lalu hitung ulang nilai.
     */
    public function nilaiEsai(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan, JawabanSiswa $jawaban): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);
        abort_unless($jawaban->ujian_pengerjaan_id === $pengerjaan->id, 404);
        abort_unless($jawaban->soal?->tipe === 'esai', 404);

        $data = $request->validate([
            'skor' => ['required', 'numeric', 'min:0', 'max:'.$jawaban->soal->poin],
        ]);

        $jawaban->update(['skor' => $data['skor'], 'benar' => null]);

        $this->ujianService->rekalkulasiNilai($pengerjaan->fresh());

        Toast::success('Skor esai disimpan.');

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function hasilData(Ujian $ujian): array
    {
        $ujian->load(['guruKelas', 'pengerjaans']);
        $guruKelas = $ujian->guruKelas;

        $pengerjaanByNisn = $ujian->pengerjaans
            ->sortByDesc('attempt_ke')
            ->unique('siswa_nisn')
            ->keyBy('siswa_nisn');

        $siswas = SiswaKelas::query()
            ->with('siswa')
            ->where('kelas_id', $guruKelas->kelas_id)
            ->where('tahun_ajaran_id', $guruKelas->tahun_ajaran_id)
            ->where('active', true)
            ->orderBy('siswa_nisn')
            ->get()
            ->map(function (SiswaKelas $sk) use ($pengerjaanByNisn): array {
                $p = $pengerjaanByNisn->get($sk->siswa_nisn);

                return [
                    'nisn' => $sk->siswa_nisn,
                    'nama' => $sk->siswa?->nama_lengkap ?? 'Siswa',
                    'pengerjaan_id' => $p?->id,
                    'status' => $p?->status ?? 'belum',
                    'nilai_total' => $p?->nilai_total,
                    'nilai_objektif' => $p?->nilai_objektif,
                    'nilai_esai' => $p?->nilai_esai,
                    'jumlah_pelanggaran' => $p?->jumlah_pelanggaran ?? 0,
                    'submitted_at' => $p?->submitted_at?->translatedFormat('d M Y H:i'),
                ];
            });

        return [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
                'jumlah_soal' => $ujian->soals()->count(),
                'ada_esai' => $ujian->soals()->where('tipe', 'esai')->exists(),
            ],
            'siswas' => $siswas,
        ];
    }
}
