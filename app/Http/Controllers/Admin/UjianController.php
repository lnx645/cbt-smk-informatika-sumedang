<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruKelas;
use App\Models\JawabanSiswa;
use App\Models\OpsiSoal;
use App\Models\Penilaian;
use App\Models\PeriodeUjian;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UjianController extends Controller
{
    public function __construct(private readonly UjianService $ujianService)
    {
        parent::__construct();
    }

    /**
     * Monitoring seluruh ujian lintas guru.
     */
    public function index(Request $request): Response
    {
        $ujians = Ujian::query()
            ->with(['guru:id,nama_lengkap', 'guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name'])
            ->withCount('soals')
            ->withCount(['pengerjaans as jumlah_selesai' => fn ($q) => $q->whereIn('status', ['selesai', 'auto_submit'])])
            ->when($request->string('kategori')->toString(), fn ($q, $k) => $q->where('kategori', $k))
            ->when($request->string('q')->toString(), fn ($q, $kw) => $q->where('judul', 'like', "%{$kw}%"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $ujians->through(fn (Ujian $u): array => [
            'id' => $u->id,
            'judul' => $u->judul,
            'kategori' => $u->kategori,
            'guru' => $u->guru?->nama_lengkap,
            'kelas' => $u->guruKelas?->kelas?->nama,
            'matpel' => $u->guruKelas?->matpel?->name,
            'status' => $u->status,
            'jumlah_soal' => $u->soals_count,
            'jumlah_selesai' => $u->jumlah_selesai,
            'dibuat_oleh_admin' => $u->dibuat_oleh_admin,
            'token' => $u->token,
            'token_released' => $u->token_released_at !== null,
            'token_pengelola' => $u->token_pengelola,
            'tanggal_mulai' => $u->tanggal_mulai?->translatedFormat('d M Y H:i'),
        ]);

        return Inertia::render('admin/Ujian/Index', [
            'ujians' => $ujians,
            'penugasan' => $this->penugasan(),
            'filters' => [
                'kategori' => $request->string('kategori')->toString() ?: null,
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUjian($request);
        $guruKelas = GuruKelas::findOrFail($data['guru_kelas_id']);

        $ujian = new Ujian($data);
        $ujian->guru_id = $guruKelas->guru_id;
        $ujian->dibuat_oleh_admin = true;
        $ujian->status = 'draft';
        $ujian->save();

        $penilaian = Penilaian::create([
            'nama' => strtoupper($ujian->kategori).': '.$ujian->judul,
            'deskripsi' => $ujian->deskripsi,
            'tipe' => 'cbt',
            'nilai_maks' => $ujian->nilai_maks,
            'bobot' => $ujian->bobot,
            'aktif' => true,
            'sumber' => $ujian->kategori,
        ]);
        $ujian->update(['penilaian_id' => $penilaian->id]);

        Toast::success('Ujian dibuat.');

        return Redirect::back();
    }

    public function destroy(Ujian $ujian): RedirectResponse
    {
        $penilaianId = $ujian->penilaian_id;
        $ujian->delete();

        if ($penilaianId) {
            Penilaian::where('id', $penilaianId)->delete();
        }

        Toast::success('Ujian dihapus.');

        return Redirect::back();
    }

    public function terbit(Ujian $ujian): RedirectResponse
    {
        if ($ujian->status === 'draft') {
            if ($ujian->soals()->count() === 0) {
                Toast::error('Ujian belum memiliki soal.');

                return Redirect::back();
            }
            $ujian->update(['status' => 'terbit']);
            Toast::success('Ujian diterbitkan.');
        } else {
            $ujian->update(['status' => 'draft']);
            Toast::info('Ujian ditarik ke draft.');
        }

        return Redirect::back();
    }

    /**
     * Atur siapa pengelola token (guru pengampu atau admin).
     */
    public function setPengelolaToken(Request $request, Ujian $ujian): RedirectResponse
    {
        $data = $request->validate([
            'token_pengelola' => ['required', Rule::in(['guru', 'admin'])],
        ]);

        $ujian->update(['token_pengelola' => $data['token_pengelola']]);

        Toast::success('Pengelola token diatur ke '.strtoupper($data['token_pengelola']).'.');

        return Redirect::back();
    }

    /**
     * Buat / regenerasi token (admin, untuk ujian yang dikelola admin).
     */
    public function generateToken(Ujian $ujian): RedirectResponse
    {
        if ($ujian->token_pengelola !== 'admin') {
            Toast::error('Token ujian ini dikelola oleh guru pengampu.');

            return Redirect::back();
        }

        $ujian->update([
            'token' => $this->ujianService->generateToken(),
            'token_released_at' => null,
        ]);

        Toast::success('Token baru dibuat. Rilis token saat siswa siap.');

        return Redirect::back();
    }

    /**
     * Rilis / tahan token (admin).
     */
    public function toggleToken(Ujian $ujian): RedirectResponse
    {
        if ($ujian->token_pengelola !== 'admin') {
            Toast::error('Token ujian ini dikelola oleh guru pengampu.');

            return Redirect::back();
        }

        if ($ujian->token === null) {
            Toast::error('Buat token terlebih dahulu.');

            return Redirect::back();
        }

        if ($ujian->token_released_at === null) {
            $ujian->update(['token_released_at' => now()]);
            Toast::success('Token dirilis. Siswa dapat memulai ujian.');
        } else {
            $ujian->update(['token_released_at' => null]);
            Toast::info('Token ditahan.');
        }

        return Redirect::back();
    }

    /**
     * Lihat bank soal ujian (monitoring lintas guru, read-only).
     */
    public function soal(Ujian $ujian): Response
    {
        $ujian->load(['guru:id,nama_lengkap', 'guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'soals.opsi']);

        return Inertia::render('admin/Ujian/Soal', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'status' => $ujian->status,
                'guru' => $ujian->guru?->nama_lengkap,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'total_poin' => (int) $ujian->soals->sum('poin'),
            ],
            'soals' => $ujian->soals->map(fn (Soal $s): array => [
                'id' => $s->id,
                'tipe' => $s->tipe,
                'pertanyaan' => $s->pertanyaan,
                'poin' => $s->poin,
                'kunci_isian' => $s->kunci_isian,
                'opsi' => $s->opsi->map(fn (OpsiSoal $o): array => [
                    'id' => $o->id,
                    'teks' => $o->teks,
                    'benar' => $o->benar,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Rekap hasil ujian lintas siswa (monitoring).
     */
    public function hasil(Ujian $ujian): Response
    {
        $ujian->load(['guru:id,nama_lengkap', 'guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'pengerjaans']);
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

        return Inertia::render('admin/Ujian/Hasil', [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'nilai_maks' => $ujian->nilai_maks,
                'guru' => $ujian->guru?->nama_lengkap,
                'kelas' => $guruKelas->kelas?->nama,
                'matpel' => $guruKelas->matpel?->name,
                'jumlah_soal' => $ujian->soals()->count(),
                'ada_esai' => $ujian->soals()->where('tipe', 'esai')->exists(),
            ],
            'siswas' => $siswas,
        ]);
    }

    /**
     * Detail satu pengerjaan + koreksi esai (admin akses penuh).
     */
    public function hasilShow(Ujian $ujian, UjianPengerjaan $pengerjaan): Response
    {
        abort_unless($pengerjaan->ujian_id === $ujian->id, 404);

        $pengerjaan->load(['jawabans.soal.opsi', 'pelanggarans', 'siswa']);

        return Inertia::render('admin/Ujian/Koreksi', [
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
                'opsi' => $j->soal?->opsi->map(fn (OpsiSoal $o): array => [
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
     * Simpan skor esai (admin) lalu hitung ulang nilai.
     */
    public function nilaiEsai(Request $request, Ujian $ujian, UjianPengerjaan $pengerjaan, JawabanSiswa $jawaban): RedirectResponse
    {
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
     * @return array<int, array{value: int, label: string}>
     */
    private function penugasan(): array
    {
        return GuruKelas::query()
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->with(['guru:id,nama_lengkap', 'kelas:id,nama', 'matpel:id,name'])
            ->orderBy('kelas_id')
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'value' => $gk->id,
                'label' => ($gk->guru?->nama_lengkap ?? 'Guru').' · '.($gk->kelas?->nama ?? 'Kelas').' — '.($gk->matpel?->name ?? 'Matpel'),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUjian(Request $request): array
    {
        $data = $request->validate([
            'guru_kelas_id' => [
                'required',
                Rule::exists('guru_kelas', 'id')
                    ->where('aktif', true)
                    ->where('tahun_ajaran_id', $this->tahunAjaran?->id),
            ],
            'kategori' => ['required', Rule::in(['kuis', 'uts', 'uas', 'usbk'])],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'durasi_menit' => ['required', 'integer', 'min:1', 'max:600'],
            'maks_attempt' => ['required', 'integer', 'min:1', 'max:10'],
            'acak_soal' => ['boolean'],
            'acak_opsi' => ['boolean'],
            'tampilkan_hasil' => ['boolean'],
            'wajib_fullscreen' => ['boolean'],
            'maks_pelanggaran' => ['required', 'integer', 'min:0', 'max:50'],
            'nilai_maks' => ['required', 'integer', 'min:1', 'max:1000'],
            'bobot' => ['required', 'integer', 'min:1', 'max:100'],
            'token_pengelola' => ['sometimes', Rule::in(['guru', 'admin'])],
        ]);

        if (in_array($data['kategori'], Ujian::KATEGORI_BESAR, true)) {
            if (empty($data['tanggal_mulai']) || empty($data['tanggal_selesai'])) {
                throw ValidationException::withMessages([
                    'tanggal_mulai' => 'Ujian besar wajib memiliki jadwal.',
                ]);
            }

            $ok = PeriodeUjian::where('kategori', $data['kategori'])
                ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
                ->where('tanggal_mulai', '<=', $data['tanggal_mulai'])
                ->where('tanggal_selesai', '>=', $data['tanggal_selesai'])
                ->exists();

            if (! $ok) {
                throw ValidationException::withMessages([
                    'tanggal_mulai' => 'Jadwal harus berada dalam periode '.strtoupper($data['kategori']).'.',
                ]);
            }
        }

        return $data;
    }
}
