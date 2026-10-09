<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Matpel;
use App\Models\Soal;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BankSoalController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $bank = $this->queryBankSoal($request)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $bank->through(fn (BankSoal $b): array => $this->mapBankSoal($b));

        // Hitungan TOTAL per tipe (seluruh bank, bukan cuma halaman aktif)
        $stats = BankSoal::query()
            ->when($request->integer('matpel_id'), fn ($q, $id) => $q->where('matpel_id', $id))
            ->select('tipe')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('tipe')
            ->pluck('jumlah', 'tipe');

        return Inertia::render('admin/BankSoal/Index', [
            'bank' => $bank,
            'matpelOptions' => Matpel::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Matpel $m): array => ['value' => $m->id, 'label' => $m->name]),
            'filters' => $this->readFilters($request),
            'stats' => [
                'total' => $stats->sum(),
                'pg' => $stats->get('pg', 0),
                'multi' => $stats->get('multi', 0),
                'benar_salah' => $stats->get('benar_salah', 0),
                'isian' => $stats->get('isian', 0),
                'esai' => $stats->get('esai', 0),
            ],
        ]);
    }

    // ── Store ───────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBankSoal($request);

        DB::transaction(function () use ($data): void {
            $bank = BankSoal::create($this->buildBankSoalData($data));
            $this->syncOpsi($bank, $data);
        });

        Toast::success('Soal ditambahkan ke bank.');

        return Redirect::back();
    }

    // ── Update ──────────────────────────────────────────────────────────────

    public function update(Request $request, BankSoal $bankSoal): RedirectResponse
    {
        $data = $this->validateBankSoal($request);

        DB::transaction(function () use ($bankSoal, $data): void {
            $bankSoal->update($this->buildBankSoalData($data));
            $bankSoal->opsi()->delete();
            $this->syncOpsi($bankSoal, $data);
        });

        Toast::success('Soal bank diperbarui.');

        return Redirect::back();
    }

    // ── Destroy ────────────────────────────────────────────────────────────

    public function destroy(BankSoal $bankSoal): RedirectResponse
    {
        $bankSoal->delete();

        Toast::success('Soal bank dihapus.');

        return Redirect::back();
    }

    // ── Private: Query & Mapping ─────────────────────────────────────────────

    private function queryBankSoal(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return BankSoal::query()
            ->with(['matpel:id,name', 'guru:id,nama_lengkap', 'opsi:id,bank_soal_id,teks,benar'])
            ->when($request->integer('matpel_id'), fn ($q, $id) => $q->where('matpel_id', $id))
            ->when($request->string('tipe')->toString(), fn ($q, $t) => $q->where('tipe', $t))
            ->when($request->string('kesulitan')->toString(), fn ($q, $k) => $q->where('kesulitan', $k))
            ->when($request->string('q')->toString(), fn ($q, $kw) => $q->where('pertanyaan', 'like', "%{$kw}%"));
    }

    private function mapBankSoal(BankSoal $b): array
    {
        return [
            'id' => $b->id,
            'matpel_id' => $b->matpel_id,
            'matpel' => $b->matpel?->name,
            'guru' => $b->guru?->nama_lengkap,
            'tipe' => $b->tipe,
            'pertanyaan' => $b->pertanyaan,
            'poin' => $b->poin,
            'topik' => $b->topik,
            'kesulitan' => $b->kesulitan,
            'kunci_isian' => $b->kunci_isian,
            'isian_case_sensitive' => $b->isian_case_sensitive,
            'opsi' => $b->opsi->map(fn ($o): array => [
                'id' => $o->id,
                'teks' => $o->teks,
                'benar' => $o->benar,
            ])->values(),
        ];
    }

    /** @return array{matpel_id: int|null, tipe: string|null, kesulitan: string|null, q: string} */
    private function readFilters(Request $request): array
    {
        return [
            'matpel_id' => $request->integer('matpel_id') ?: null,
            'tipe' => $request->string('tipe')->toString() ?: null,
            'kesulitan' => $request->string('kesulitan')->toString() ?: null,
            'q' => $request->string('q')->toString(),
        ];
    }

    // ── Private: Validation ─────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validateBankSoal(Request $request): array
    {
        $data = $request->validate([
            'matpel_id' => ['required', 'exists:matpels,id'],
            'tipe' => ['required', Rule::in(Soal::TIPE)],
            'pertanyaan' => ['required', 'string', 'max:20000'],
            'poin' => ['required', 'integer', 'min:1', 'max:100'],
            'topik' => ['nullable', 'string', 'max:100'],
            'kesulitan' => ['required', Rule::in(BankSoal::KESULITAN)],
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
     * @param  array<string, mixed>  $data
     */
    private function validateStruktur(array $data): void
    {
        $tipe = $data['tipe'];
        $opsi = collect($data['opsi'] ?? []);

        if (in_array($tipe, BankSoal::OPSI_TIPE, true)) {
            $this->validateOpsi($tipe, $opsi);
        } elseif ($tipe === 'isian') {
            $this->validateIsian($data);
        }
    }

    private function validateOpsi(string $tipe, Collection $opsi): void
    {
        if ($opsi->count() < 2) {
            throw ValidationException::withMessages(['opsi' => 'Sediakan minimal 2 opsi jawaban.']);
        }

        if ($opsi->contains(fn ($o): bool => trim((string) ($o['teks'] ?? '')) === '')) {
            throw ValidationException::withMessages(['opsi' => 'Semua opsi jawaban harus diisi.']);
        }

        $benar = $opsi->filter(fn ($o): bool => (bool) ($o['benar'] ?? false));

        if (in_array($tipe, ['pg', 'benar_salah'], true)) {
            if ($benar->count() !== 1) {
                throw ValidationException::withMessages(['opsi' => 'Tandai tepat satu opsi sebagai jawaban benar.']);
            }
        } elseif ($tipe === 'multi' && $benar->count() < 1) {
            throw ValidationException::withMessages(['opsi' => 'Tandai minimal satu opsi sebagai jawaban benar.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateIsian(array $data): void
    {
        $kunci = collect($data['kunci_isian'] ?? [])
            ->filter(fn ($k): bool => trim((string) $k) !== '');

        if ($kunci->isEmpty()) {
            throw ValidationException::withMessages(['kunci_isian' => 'Isi minimal satu kunci jawaban.']);
        }
    }

    // ── Private: Persistence ───────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildBankSoalData(array $data): array
    {
        return [
            'matpel_id' => $data['matpel_id'],
            'tipe' => $data['tipe'],
            'pertanyaan' => $data['pertanyaan'],
            'poin' => $data['poin'],
            'topik' => $data['topik'] ?? null,
            'kesulitan' => $data['kesulitan'],
            'kunci_isian' => $this->kunciIsian($data),
            'isian_case_sensitive' => $data['isian_case_sensitive'] ?? false,
        ];
    }

    /**
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
    private function syncOpsi(BankSoal $bank, array $data): void
    {
        if (! $bank->butuhOpsi()) {
            return;
        }

        foreach (array_values($data['opsi'] ?? []) as $i => $opsi) {
            $bank->opsi()->create([
                'teks' => $opsi['teks'],
                'benar' => (bool) ($opsi['benar'] ?? false),
                'urutan' => $i,
            ]);
        }
    }
}
