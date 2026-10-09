<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Penilaian;
use App\Models\PeriodeUjian;
use App\Models\Ujian;
use App\Services\UjianService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                if ($request->user()?->role !== 'guru') {
                    abort(403);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Daftar ujian milik guru untuk tahun ajaran aktif.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('guru/Ujian/Index', $this->indexData($request));
    }

    /**
     * Simpan ujian baru + buat penilaian terkait.
     */
    public function store(Request $request): RedirectResponse
    {
        $guru = $request->user()->guru;
        $data = $this->validateUjian($request, $guru);

        $ujian = new Ujian($data);
        $ujian->guru_id = $guru->id;
        $ujian->dibuat_oleh_admin = false;
        $ujian->status = 'draft';
        $ujian->save();

        $this->buatPenilaian($ujian);

        Toast::success('Ujian "'.$ujian->judul.'" berhasil dibuat.');

        return Redirect::back();
    }

    /**
     * Data ujian milik guru untuk modal edit.
     */
    public function edit(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Index', $this->indexData($request) + [
            'editUjian' => $this->serializeEdit($ujian),
        ]);
    }

    public function update(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $guru = $request->user()->guru;
        $data = $this->validateUjian($request, $guru);

        $ujian->fill($data)->save();

        if ($ujian->penilaian_id) {
            $ujian->penilaian()->update([
                'nama' => $this->namaPenilaian($ujian),
                'nilai_maks' => $ujian->nilai_maks,
                'bobot' => $ujian->bobot,
            ]);
        }

        Toast::success('Ujian "'.$ujian->judul.'" berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $penilaianId = $ujian->penilaian_id;
        $ujian->delete();

        if ($penilaianId) {
            Penilaian::where('id', $penilaianId)->delete();
        }

        Toast::success('Ujian berhasil dihapus.');

        return Redirect::back();
    }

    /**
     * Terbitkan / tarik ujian. Ujian besar wajib punya minimal 1 soal.
     */
    public function terbit(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->status === 'draft') {
            if ($ujian->soals()->count() === 0) {
                Toast::error('Tambahkan minimal satu soal sebelum menerbitkan ujian.');

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
     * Buat / regenerasi token acak untuk ujian.
     */
    public function generateToken(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->token_pengelola !== 'guru') {
            Toast::error('Token ujian ini dikelola oleh admin.');

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
     * Rilis / tahan token (buka gerbang pengerjaan).
     */
    public function toggleToken(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        if ($ujian->token_pengelola !== 'guru') {
            Toast::error('Token ujian ini dikelola oleh admin.');

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
     * @return array<string, mixed>
     */
    private function indexData(Request $request): array
    {
        $guru = $request->user()->guru;

        $ujians = Ujian::query()
            ->with(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name'])
            ->withCount('soals')
            ->withCount(['pengerjaans as jumlah_selesai' => fn ($q) => $q->whereIn('status', ['selesai', 'auto_submit'])])
            ->where('guru_id', $guru->id)
            ->whereHas('guruKelas', fn ($q) => $q->where('tahun_ajaran_id', $this->tahunAjaran?->id))
            ->when($request->integer('guru_kelas_id'), fn ($q, $id) => $q->where('guru_kelas_id', $id))
            ->when($request->string('kategori')->toString(), fn ($q, $k) => $q->where('kategori', $k))
            ->when($request->string('q')->toString(), fn ($q, $kw) => $q->where('judul', 'like', "%{$kw}%"))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $ujians->through(fn (Ujian $u): array => [
            'id' => $u->id,
            'judul' => $u->judul,
            'kategori' => $u->kategori,
            'kelas' => $u->guruKelas?->kelas?->nama,
            'matpel' => $u->guruKelas?->matpel?->name,
            'status' => $u->status,
            'durasi_menit' => $u->durasi_menit,
            'tanggal_mulai' => $u->tanggal_mulai?->translatedFormat('d M Y H:i'),
            'tanggal_selesai' => $u->tanggal_selesai?->translatedFormat('d M Y H:i'),
            'jumlah_soal' => $u->soals_count,
            'jumlah_selesai' => $u->jumlah_selesai,
            'token' => $u->token,
            'token_released' => $u->token_released_at !== null,
            'token_pengelola' => $u->token_pengelola,
            'nilai_maks' => $u->nilai_maks,
            'bobot' => $u->bobot,
        ]);

        return [
            'ujians' => $ujians,
            'penugasan' => $this->penugasan($guru),
            'filters' => [
                'guru_kelas_id' => $request->integer('guru_kelas_id') ?: null,
                'kategori' => $request->string('kategori')->toString() ?: null,
                'q' => $request->string('q')->toString(),
            ],
        ];
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function penugasan(Guru $guru): array
    {
        return GuruKelas::where('guru_id', $guru->id)
            ->where('aktif', true)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->with(['kelas', 'matpel'])
            ->orderBy('kelas_id')
            ->get()
            ->map(fn (GuruKelas $gk): array => [
                'value' => $gk->id,
                'label' => ($gk->kelas?->nama ?? 'Kelas').' — '.($gk->matpel?->name ?? 'Matpel'),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEdit(Ujian $ujian): array
    {
        return [
            'id' => $ujian->id,
            'guru_kelas_id' => $ujian->guru_kelas_id,
            'kategori' => $ujian->kategori,
            'judul' => $ujian->judul,
            'deskripsi' => $ujian->deskripsi,
            'tanggal_mulai' => $ujian->tanggal_mulai?->format('Y-m-d H:i'),
            'tanggal_selesai' => $ujian->tanggal_selesai?->format('Y-m-d H:i'),
            'durasi_menit' => $ujian->durasi_menit,
            'maks_attempt' => $ujian->maks_attempt,
            'acak_soal' => $ujian->acak_soal,
            'acak_opsi' => $ujian->acak_opsi,
            'tampilkan_hasil' => $ujian->tampilkan_hasil,
            'wajib_fullscreen' => $ujian->wajib_fullscreen,
            'maks_pelanggaran' => $ujian->maks_pelanggaran,
            'nilai_maks' => $ujian->nilai_maks,
            'bobot' => $ujian->bobot,
            'token_pengelola' => $ujian->token_pengelola,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUjian(Request $request, Guru $guru): array
    {
        $data = $request->validate([
            'guru_kelas_id' => [
                'required',
                Rule::exists('guru_kelas', 'id')
                    ->where('guru_id', $guru->id)
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
        ], [], [
            'guru_kelas_id' => 'Kelas & Mata Pelajaran',
            'kategori' => 'Kategori',
            'judul' => 'Judul',
            'durasi_menit' => 'Durasi',
        ]);

        $this->pastikanDalamWindow($data['kategori'], $data['tanggal_mulai'] ?? null, $data['tanggal_selesai'] ?? null);

        return $data;
    }

    /**
     * Ujian besar (uts/uas/usbk) wajib dijadwalkan di dalam window periode admin.
     */
    private function pastikanDalamWindow(string $kategori, ?string $mulai, ?string $selesai): void
    {
        if (! in_array($kategori, Ujian::KATEGORI_BESAR, true)) {
            return;
        }

        if ($mulai === null || $selesai === null) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Ujian '.strtoupper($kategori).' wajib memiliki jadwal mulai dan selesai.',
            ]);
        }

        $periode = PeriodeUjian::where('kategori', $kategori)
            ->where('tahun_ajaran_id', $this->tahunAjaran?->id)
            ->where('tanggal_mulai', '<=', $mulai)
            ->where('tanggal_selesai', '>=', $selesai)
            ->exists();

        if (! $periode) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Jadwal harus berada dalam periode '.strtoupper($kategori).' yang ditetapkan admin.',
            ]);
        }
    }

    private function buatPenilaian(Ujian $ujian): void
    {
        $penilaian = Penilaian::create([
            'nama' => $this->namaPenilaian($ujian),
            'deskripsi' => $ujian->deskripsi,
            'tipe' => 'cbt',
            'nilai_maks' => $ujian->nilai_maks,
            'bobot' => $ujian->bobot,
            'aktif' => true,
            'sumber' => $ujian->kategori,
        ]);

        $ujian->update(['penilaian_id' => $penilaian->id]);
    }

    private function namaPenilaian(Ujian $ujian): string
    {
        return strtoupper($ujian->kategori).': '.$ujian->judul;
    }
}
