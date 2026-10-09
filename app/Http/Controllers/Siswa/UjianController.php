<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\BaseAppController;
use App\Models\JawabanSiswa;
use App\Models\OpsiSoal;
use App\Models\PelanggaranLog;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UjianController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly UjianService $ujianService)
    {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, $next) {
                if ($request->user()?->role !== 'siswa') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Daftar ujian terbit untuk kelas siswa pada tahun ajaran aktif.
     */
    public function index(Request $request): Response
    {
        $nisn = $request->user()->siswa->nisn;
        $kelasIds = $this->kelasIds($nisn);

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'guru:id,nama_lengkap'])
            ->withCount('soals')
            ->where('status', 'terbit')
            ->whereHas('guruKelas', fn ($q) => $q->whereIn('kelas_id', $kelasIds)->where('tahun_ajaran_id', $this->tahunAjaran?->id))
            ->orderByDesc('created_at')
            ->get();

        $pengerjaans = UjianPengerjaan::query()
            ->where('siswa_nisn', $nisn)
            ->whereIn('ujian_id', $ujians->pluck('id'))
            ->get()
            ->groupBy('ujian_id');

        return Inertia::render('siswa/Ujian/Index', [
            'ujians' => $ujians->map(function (Ujian $u) use ($pengerjaans): array {
                $sesi = $pengerjaans->get($u->id, collect());
                $selesai = $sesi->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi']);
                $berlangsung = $sesi->firstWhere('status', 'berlangsung');

                return [
                    'id' => $u->id,
                    'judul' => $u->judul,
                    'kategori' => $u->kategori,
                    'matpel' => $u->guruKelas?->matpel?->name,
                    'guru' => $u->guru?->nama_lengkap,
                    'durasi_menit' => $u->durasi_menit,
                    'jumlah_soal' => $u->soals_count,
                    'tanggal_mulai' => $u->tanggal_mulai?->translatedFormat('d M Y H:i'),
                    'tanggal_selesai' => $u->tanggal_selesai?->translatedFormat('d M Y H:i'),
                    'sedang_berlangsung' => $u->sedangBerlangsung(),
                    'butuh_token' => $u->token !== null,
                    'attempt_terpakai' => $selesai->count(),
                    'maks_attempt' => $u->maks_attempt,
                    'sedang_dikerjakan' => $berlangsung !== null,
                    'tampilkan_hasil' => $u->tampilkan_hasil,
                    'nilai_total' => $selesai->max('nilai_total'),
                ];
            }),
        ]);
    }

    /**
     * Mulai atau lanjutkan pengerjaan (validasi token & attempt di server).
     */
    public function mulai(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        if (! $ujian->sedangBerlangsung()) {
            Toast::error('Ujian belum dibuka atau sudah berakhir.');

            return Redirect::back();
        }

        // Sesi berjalan yang masih valid -> lanjutkan.
        $berjalan = $this->sesiBerjalan($ujian, $nisn);
        if ($berjalan) {
            Toast::info('Melanjutkan pengerjaan ujian.');

            return Redirect::route('app.siswa.ujian.kerjakan', $ujian);
        }

        if ($ujian->token !== null) {
            $request->validate(['token' => ['required', 'string']]);

            if (! $ujian->tokenValid($request->string('token')->toString())) {
                Toast::error('Token salah atau belum dirilis pengawas.');

                return Redirect::back();
            }
        }

        $terpakai = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi'])
            ->count();

        if ($terpakai >= $ujian->maks_attempt) {
            Toast::error('Kesempatan mengerjakan sudah habis.');

            return Redirect::back();
        }

        $mulai = now();
        $batas = $mulai->copy()->addMinutes($ujian->durasi_menit);
        if ($ujian->tanggal_selesai !== null && $batas->gt($ujian->tanggal_selesai)) {
            $batas = $ujian->tanggal_selesai;
        }

        UjianPengerjaan::create([
            'ujian_id' => $ujian->id,
            'siswa_nisn' => $nisn,
            'attempt_ke' => $terpakai + 1,
            'mulai_at' => $mulai,
            'batas_at' => $batas,
            'status' => 'berlangsung',
        ]);

        Toast::success('Ujian dimulai. Kerjakan dengan jujur.');

        return Redirect::route('app.siswa.ujian.kerjakan', $ujian);
    }

    /**
     * Halaman runtime pengerjaan.
     */
    public function kerjakan(Request $request, Ujian $ujian): Response|RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);

        if (! $pengerjaan) {
            // Cek apakah waktu habis pada sesi terakhir dan finalisasi.
            $terakhir = UjianPengerjaan::where('ujian_id', $ujian->id)
                ->where('siswa_nisn', $nisn)
                ->where('status', 'berlangsung')
                ->latest('id')
                ->first();

            if ($terakhir && $terakhir->waktuHabis()) {
                $this->finalisasi($terakhir, 'auto_submit');
                Toast::warning('Waktu ujian habis. Jawaban dikumpulkan otomatis.');
            }

            return Redirect::route('app.siswa.ujian.index');
        }

        $ujian->load('soals.opsi');
        $soals = $this->urutkanSoal($ujian, $pengerjaan);

        $jawabanBySoal = $pengerjaan->jawabans()->get()->keyBy('soal_id');

        return Inertia::render('siswa/Ujian/Kerjakan', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'wajib_fullscreen' => $ujian->wajib_fullscreen,
                'maks_pelanggaran' => $ujian->maks_pelanggaran,
            ],
            'pengerjaan' => [
                'id' => $pengerjaan->id,
                'batas_at' => $pengerjaan->batas_at->toIso8601String(),
                'sisa_detik' => $pengerjaan->sisaDetik(),
                'jumlah_pelanggaran' => $pengerjaan->jumlah_pelanggaran,
            ],
            'soals' => $soals->map(fn (Soal $s): array => [
                'id' => $s->id,
                'tipe' => $s->tipe,
                'pertanyaan' => $s->pertanyaan,
                'poin' => $s->poin,
                'opsi' => $s->butuhOpsi()
                    ? $this->urutkanOpsi($s, $ujian, $pengerjaan)->map(fn ($o): array => [
                        'id' => $o->id,
                        'teks' => $o->teks,
                    ])->values()
                    : [],
                'jawaban' => [
                    'opsi_dipilih' => $jawabanBySoal->get($s->id)?->opsi_dipilih,
                    'jawaban_teks' => $jawabanBySoal->get($s->id)?->jawaban_teks,
                ],
            ])->values(),
        ]);
    }

    /**
     * Autosave satu jawaban.
     */
    public function simpanJawaban(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            return Redirect::back();
        }

        $data = $request->validate([
            'soal_id' => ['required', Rule::exists('soals', 'id')->where('ujian_id', $ujian->id)],
            'opsi_dipilih' => ['nullable', 'array'],
            'opsi_dipilih.*' => ['integer'],
            'jawaban_teks' => ['nullable', 'string', 'max:20000'],
        ]);

        if ($pengerjaan->waktuHabis()) {
            $this->finalisasi($pengerjaan, 'auto_submit');
            Toast::warning('Waktu ujian habis. Jawaban dikumpulkan otomatis.');

            return Redirect::route('app.siswa.ujian.index');
        }

        JawabanSiswa::updateOrCreate(
            ['ujian_pengerjaan_id' => $pengerjaan->id, 'soal_id' => $data['soal_id']],
            ['opsi_dipilih' => $data['opsi_dipilih'] ?? null, 'jawaban_teks' => $data['jawaban_teks'] ?? null],
        );

        return Redirect::back();
    }

    /**
     * Kumpulkan ujian (auto-grade objektif).
     */
    public function submit(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            Toast::error('Sesi ujian tidak ditemukan atau sudah berakhir.');

            return Redirect::route('app.siswa.ujian.index');
        }

        $this->finalisasi($pengerjaan, 'selesai');

        Toast::success('Ujian berhasil dikumpulkan.');

        return Redirect::route('app.siswa.ujian.hasil', $ujian);
    }

    /**
     * Catat pelanggaran proctoring; auto-submit bila melebihi ambang.
     */
    public function lapor(Request $request, Ujian $ujian): RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = $this->sesiBerjalan($ujian, $nisn);
        if (! $pengerjaan) {
            return Redirect::back();
        }

        $data = $request->validate([
            'jenis' => ['required', Rule::in(PelanggaranLog::JENIS)],
        ]);

        $pengerjaan->pelanggarans()->create([
            'jenis' => $data['jenis'],
            'terjadi_at' => now(),
        ]);

        $pengerjaan->increment('jumlah_pelanggaran');

        if ($ujian->maks_pelanggaran > 0 && $pengerjaan->jumlah_pelanggaran >= $ujian->maks_pelanggaran) {
            $this->finalisasi($pengerjaan, 'diskualifikasi');
            Toast::error('Anda melewati batas pelanggaran. Ujian dihentikan otomatis.');

            return Redirect::route('app.siswa.ujian.index');
        }

        return Redirect::back();
    }

    /**
     * Halaman hasil ujian siswa.
     */
    public function hasil(Request $request, Ujian $ujian): Response|RedirectResponse
    {
        $nisn = $request->user()->siswa->nisn;
        $this->pastikanBolehAkses($ujian, $nisn);

        $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->whereIn('status', ['selesai', 'auto_submit', 'diskualifikasi'])
            ->latest('id')
            ->first();

        if (! $pengerjaan) {
            Toast::info('Kamu belum menyelesaikan ujian ini.');

            return Redirect::route('app.siswa.ujian.index');
        }

        $data = [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
                'tampilkan_hasil' => $ujian->tampilkan_hasil,
            ],
            'pengerjaan' => [
                'status' => $pengerjaan->status,
                'nilai_objektif' => $pengerjaan->nilai_objektif,
                'nilai_esai' => $pengerjaan->nilai_esai,
                'nilai_total' => $pengerjaan->nilai_total,
                'submitted_at' => $pengerjaan->submitted_at?->translatedFormat('d M Y H:i'),
                'menunggu_koreksi' => $pengerjaan->nilai_total === null,
            ],
        ];

        return Inertia::render('siswa/Ujian/Hasil', $data);
    }

    /**
     * Finalisasi sesi: auto-grade objektif lalu hitung nilai.
     */
    private function finalisasi(UjianPengerjaan $pengerjaan, string $status): void
    {
        $pengerjaan->update([
            'status' => $status,
            'submitted_at' => now(),
        ]);

        $this->ujianService->gradeObjektif($pengerjaan);
        $this->ujianService->rekalkulasiNilai($pengerjaan->fresh());
    }

    private function sesiBerjalan(Ujian $ujian, string $nisn): ?UjianPengerjaan
    {
        $pengerjaan = UjianPengerjaan::where('ujian_id', $ujian->id)
            ->where('siswa_nisn', $nisn)
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

        if ($pengerjaan && $pengerjaan->waktuHabis()) {
            $this->finalisasi($pengerjaan, 'auto_submit');

            return null;
        }

        return $pengerjaan;
    }

    private function pastikanBolehAkses(Ujian $ujian, string $nisn): void
    {
        abort_unless($ujian->status === 'terbit', 404);
        abort_unless($this->kelasIds($nisn)->contains($ujian->guruKelas->kelas_id), 404);
        abort_unless($ujian->guruKelas->tahun_ajaran_id === $this->tahunAjaran?->id, 404);
    }

    /**
     * @return Collection<int, int>
     */
    private function kelasIds(string $nisn): Collection
    {
        return SiswaKelas::query()
            ->where('siswa_nisn', $nisn)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->where('active', true)
            ->pluck('kelas_id');
    }

    /**
     * @return Collection<int, Soal>
     */
    private function urutkanSoal(Ujian $ujian, UjianPengerjaan $pengerjaan): Collection
    {
        $soals = $ujian->soals;

        if ($ujian->acak_soal) {
            return $soals->shuffle($pengerjaan->id);
        }

        return $soals->values();
    }

    /**
     * @return Collection<int, OpsiSoal>
     */
    private function urutkanOpsi(Soal $soal, Ujian $ujian, UjianPengerjaan $pengerjaan): Collection
    {
        if ($ujian->acak_opsi) {
            return $soal->opsi->shuffle($pengerjaan->id + $soal->id);
        }

        return $soal->opsi->values();
    }
}
