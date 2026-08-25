<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\BankSoal;
use App\Models\OpsiSoal;
use App\Models\Soal;
use App\Models\Ujian;
use App\Services\BankSoalService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SoalController extends BaseAppController implements HasMiddleware
{
    public function __construct(private readonly BankSoalService $bankSoalService)
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
     * Halaman bank soal untuk satu ujian.
     */
    public function index(Request $request, Ujian $ujian): Response
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        return Inertia::render('guru/Ujian/Soal', $this->soalData($ujian));
    }

    public function store(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $data = $this->validateSoal($request);

        DB::transaction(function () use ($ujian, $data): void {
            $urutan = (int) $ujian->soals()->max('urutan') + 1;

            $soal = $ujian->soals()->create([
                'tipe' => $data['tipe'],
                'pertanyaan' => $data['pertanyaan'],
                'poin' => $data['poin'],
                'urutan' => $urutan,
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
            ]);

            $this->simpanOpsi($soal, $data);
        });

        Toast::success('Soal berhasil ditambahkan.');

        return Redirect::back();
    }

    public function update(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        $data = $this->validateSoal($request);

        DB::transaction(function () use ($soal, $data): void {
            $soal->update([
                'tipe' => $data['tipe'],
                'pertanyaan' => $data['pertanyaan'],
                'poin' => $data['poin'],
                'kunci_isian' => $this->kunciIsian($data),
                'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
            ]);

            $soal->opsi()->delete();
            $this->simpanOpsi($soal, $data);
        });

        Toast::success('Soal berhasil diperbarui.');

        return Redirect::back();
    }

    public function destroy(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        $soal->delete();

        Toast::success('Soal dihapus.');

        return Redirect::back();
    }

    /**
     * Ambil (salin) soal terpilih dari bank ke ujian.
     */
    public function ambilDariBank(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $matpelId = $ujian->guruKelas->matpel_id;

        $data = $request->validate([
            'bank_soal_ids' => ['required', 'array', 'min:1'],
            'bank_soal_ids.*' => [Rule::exists('bank_soals', 'id')->where('matpel_id', $matpelId)],
        ]);

        $jumlah = $this->bankSoalService->salinKeUjian($ujian, $data['bank_soal_ids']);

        Toast::success($jumlah.' soal disalin dari bank.');

        return Redirect::back();
    }

    /**
     * Simpan satu soal ujian ke bank.
     */
    public function simpanKeBank(Request $request, Ujian $ujian, Soal $soal): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);
        abort_unless($soal->ujian_id === $ujian->id, 404);

        $data = $request->validate([
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['required', Rule::in(BankSoal::KESULITAN)],
        ]);

        $this->bankSoalService->simpanDariSoal(
            $soal,
            $ujian->guruKelas->matpel_id,
            $request->user()->guru?->id,
            ['topik' => $data['topik'] ?? null, 'kesulitan' => $data['kesulitan']],
        );

        Toast::success('Soal disimpan ke bank.');

        return Redirect::back();
    }

    /**
     * Generate soal acak dari bank sesuai kriteria lalu salin ke ujian.
     */
    public function generateDariBank(Request $request, Ujian $ujian): RedirectResponse
    {
        abort_unless($ujian->guru_id === $request->user()->guru?->id, 404);

        $matpelId = $ujian->guruKelas->matpel_id;

        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
            'tipe' => ['nullable', Rule::in(Soal::TIPE)],
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['nullable', Rule::in(BankSoal::KESULITAN)],
        ]);

        $jumlah = $this->bankSoalService->generate($ujian, [
            'matpel_id' => $matpelId,
            'jumlah' => $data['jumlah'],
            'tipe' => $data['tipe'] ?? null,
            'topik' => $data['topik'] ?? null,
            'kesulitan' => $data['kesulitan'] ?? null,
        ]);

        if ($jumlah === 0) {
            Toast::warning('Tidak ada soal bank yang cocok dengan kriteria.');
        } else {
            Toast::success($jumlah.' soal acak ditambahkan dari bank.');
        }

        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    private function soalData(Ujian $ujian): array
    {
        $ujian->load(['guruKelas.kelas:id,nama', 'guruKelas.matpel:id,name', 'soals.opsi']);

        $matpelId = $ujian->guruKelas?->matpel_id;

        $bank = BankSoal::query()
            ->with('opsi:id,bank_soal_id,teks,benar')
            ->where('matpel_id', $matpelId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BankSoal $b): array => [
                'id' => $b->id,
                'tipe' => $b->tipe,
                'pertanyaan' => $b->pertanyaan,
                'poin' => $b->poin,
                'topik' => $b->topik,
                'kesulitan' => $b->kesulitan,
                'jumlah_opsi' => $b->opsi->count(),
            ]);

        return [
            'ujian' => [
                'id' => $ujian->id,
                'judul' => $ujian->judul,
                'kategori' => $ujian->kategori,
                'status' => $ujian->status,
                'kelas' => $ujian->guruKelas?->kelas?->nama,
                'matpel' => $ujian->guruKelas?->matpel?->name,
                'matpel_id' => $ujian->guruKelas?->matpel_id,
                'nilai_maks' => $ujian->nilai_maks,
                'total_poin' => (int) $ujian->soals->sum('poin'),
            ],
            'soals' => $ujian->soals->map(fn (Soal $s): array => [
                'id' => $s->id,
                'tipe' => $s->tipe,
                'pertanyaan' => $s->pertanyaan,
                'poin' => $s->poin,
                'urutan' => $s->urutan,
                'kunci_isian' => $s->kunci_isian,
                'isian_case_sensitive' => $s->isian_case_sensitive,
                'opsi' => $s->opsi->map(fn (OpsiSoal $o): array => [
                    'id' => $o->id,
                    'teks' => $o->teks,
                    'benar' => $o->benar,
                ])->values(),
            ])->values(),
            'bank' => $bank,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSoal(Request $request): array
    {
        $data = $request->validate([
            'tipe' => ['required', Rule::in(Soal::TIPE)],
            'pertanyaan' => ['required', 'string', 'max:20000'],
            'poin' => ['required', 'integer', 'min:1', 'max:100'],
            'opsi' => ['array'],
            'opsi.*.teks' => ['nullable', 'string', 'max:2000'],
            'opsi.*.benar' => ['boolean'],
            'kunci_isian' => ['array'],
            'kunci_isian.*' => ['nullable', 'string', 'max:255'],
            'isian_case_sensitive' => ['boolean'],
        ]);

        $this->validateStruktur($data);

        return $data;
    }

    /**
     * Validasi struktur opsi/kunci sesuai tipe soal.
     *
     * @param  array<string, mixed>  $data
     */
    private function validateStruktur(array $data): void
    {
        $tipe = $data['tipe'];
        $opsi = collect($data['opsi'] ?? []);
        $benar = $opsi->filter(fn ($o): bool => (bool) ($o['benar'] ?? false));

        if (in_array($tipe, ['pg', 'benar_salah', 'multi'], true)) {
            if ($opsi->count() < 2) {
                throw ValidationException::withMessages(['opsi' => 'Sediakan minimal 2 opsi jawaban.']);
            }

            if ($opsi->contains(fn ($o): bool => trim((string) ($o['teks'] ?? '')) === '')) {
                throw ValidationException::withMessages(['opsi' => 'Semua opsi jawaban harus diisi.']);
            }

            if (in_array($tipe, ['pg', 'benar_salah'], true) && $benar->count() !== 1) {
                throw ValidationException::withMessages(['opsi' => 'Tandai tepat satu opsi sebagai jawaban benar.']);
            }

            if ($tipe === 'multi' && $benar->count() < 1) {
                throw ValidationException::withMessages(['opsi' => 'Tandai minimal satu opsi sebagai jawaban benar.']);
            }
        } elseif ($tipe === 'isian') {
            $kunci = collect($data['kunci_isian'] ?? [])->filter(fn ($k): bool => trim((string) $k) !== '');
            if ($kunci->isEmpty()) {
                throw ValidationException::withMessages(['kunci_isian' => 'Isi minimal satu kunci jawaban.']);
            }
        }
    }

    /**
     * Normalisasi kunci jawaban isian: trim & buang yang kosong.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>|null
     */
    private function kunciIsian(array $data): ?array
    {
        if (($data['tipe'] ?? null) !== 'isian') {
            return null;
        }

        return array_values(array_filter(
            array_map(fn ($k): string => trim((string) $k), $data['kunci_isian'] ?? []),
            fn (string $k): bool => $k !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanOpsi(Soal $soal, array $data): void
    {
        if (! $soal->butuhOpsi()) {
            return;
        }

        foreach (array_values($data['opsi'] ?? []) as $i => $opsi) {
            $soal->opsi()->create([
                'teks' => $opsi['teks'],
                'benar' => (bool) ($opsi['benar'] ?? false),
                'urutan' => $i,
            ]);
        }
    }
}
