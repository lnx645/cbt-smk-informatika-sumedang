<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseAppController;
use App\Models\GuruKelas;
use App\Models\KelompokBankSoal;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Kelompok Bank Soal — wadah bernama berisi kumpulan soal.
 * Alur: buat bank (nama via modal) -> buka bank -> isi soal.
 */
class KelompokBankSoalController extends BaseAppController
{
    public function index(Request $request): Response
    {
        $guruId = $request->user()->guru?->id;
        $matpelIds = GuruKelas::where('guru_id', $guruId)->where('aktif', true)
            ->distinct()->pluck('matpel_id')->all();

        $kelompoks = KelompokBankSoal::with(['matpel:id,name'])
            ->withCount('soals')
            ->whereIn('matpel_id', $matpelIds)
            ->orderByDesc('created_at')
            ->paginate(12);

        $matpelOptions = GuruKelas::where('guru_id', $guruId)->where('aktif', true)
            ->with('matpel:id,name')->get()
            ->pluck('matpel')->unique('id')->filter()
            ->map(fn ($m) => ['value' => $m->id, 'label' => $m->name])->values();

        return Inertia::render('guru/BankSoal/Kelompok', [
            'kelompoks' => $kelompoks,
            'matpelOptions' => $matpelOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $guruId = $request->user()->guru?->id;

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'matpel_id' => ['required', 'integer', 'exists:matpels,id'],
        ]);

        $kelompok = KelompokBankSoal::create([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'matpel_id' => $data['matpel_id'],
            'guru_id' => $guruId,
        ]);

        Toast::success("Bank soal \"{$kelompok->nama}\" dibuat. Silakan isi soal-soalnya.");

        return Redirect::route('app.guru.bank-soal.kelompok.show', $kelompok);
    }

    public function show(Request $request, KelompokBankSoal $kelompok): Response
    {
        $guruId = $request->user()->guru?->id;
        abort_unless($kelompok->guru_id === $guruId, 404);

        $kelompok->load(['matpel:id,name', 'soals.opsi']);

        return Inertia::render('guru/BankSoal/KelompokDetail', [
            'kelompok' => $kelompok,
        ]);
    }

    public function destroy(Request $request, KelompokBankSoal $kelompok): RedirectResponse
    {
        abort_unless($kelompok->guru_id === $request->user()->guru?->id, 404);

        $kelompok->delete();
        Toast::success('Bank soal dihapus.');

        return Redirect::route('app.guru.bank-soal.kelompok.index');
    }

    /**
     * Tambah soal ke dalam bank.
     */
    public function storeSoal(Request $request, KelompokBankSoal $kelompok): RedirectResponse
    {
        $guru = $request->user()->guru;
        abort_unless($kelompok->guru_id === $guru?->id, 404);

        $data = $request->validate([
            'tipe' => ['required', 'in:pg,multi,benar_salah,isian,esai'],
            'pertanyaan' => ['required', 'string'],
            'poin' => ['required', 'integer', 'min:1'],
            'opsi' => ['nullable', 'array'],
            'opsi.*.teks' => ['required_with:opsi', 'string'],
            'opsi.*.benar' => ['boolean'],
            'kunci_isian' => ['nullable', 'string'],
        ]);

        \App\Models\BankSoal::create([
            'kelompok_bank_soal_id' => $kelompok->id,
            'matpel_id' => $kelompok->matpel_id,
            'guru_id' => $guru->id,
            'tipe' => $data['tipe'],
            'pertanyaan' => \App\Support\HtmlSanitizer::clean($data['pertanyaan']),
            'poin' => $data['poin'],
            'kunci_isian' => $data['kunci_isian'] ?? null,
        ]);

        // Simpan opsi bila ada.
        if (! empty($data['opsi'])) {
            $bank = \App\Models\BankSoal::latest('id')->first();
            foreach ($data['opsi'] as $i => $o) {
                $bank->opsi()->create([
                    'teks' => $o['teks'],
                    'benar' => (bool) ($o['benar'] ?? false),
                    'urutan' => $i + 1,
                ]);
            }
        }

        Toast::success('Soal ditambahkan ke bank.');

        return Redirect::back();
    }
}
