<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeUjian;
use App\Models\TahunAjaran;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PeriodeUjianController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $periode = PeriodeUjian::query()
            ->with('tahunAjaran:id,name')
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->map(fn (PeriodeUjian $p): array => [
                'id' => $p->id,
                'kategori' => $p->kategori,
                'nama' => $p->nama,
                'tahun_ajaran' => $p->tahunAjaran?->name,
                'tahun_ajaran_id' => $p->tahun_ajaran_id,
                'tanggal_mulai' => $p->tanggal_mulai?->format('Y-m-d H:i'),
                'tanggal_selesai' => $p->tanggal_selesai?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('admin/PeriodeUjian/Index', [
            'periode' => $periode,
            'tahunAjaran' => TahunAjaran::orderByDesc('active')
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        PeriodeUjian::create($this->validateRequest($request));

        Toast::success('Periode ujian ditambahkan.');

        return Redirect::route('admin.periode-ujian.index');
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function update(Request $request, PeriodeUjian $periodeUjian): RedirectResponse
    {
        $periodeUjian->update($this->validateRequest($request));

        Toast::success('Periode ujian diperbarui.');

        return Redirect::route('admin.periode-ujian.index');
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(PeriodeUjian $periodeUjian): RedirectResponse
    {
        $periodeUjian->delete();

        Toast::success('Periode ujian dihapus.');

        return Redirect::route('admin.periode-ujian.index');
    }

    // ── Private ──────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'kategori' => ['required', Rule::in(['uts', 'uas', 'usbk'])],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ], [], [
            'kategori' => 'Kategori',
            'tahun_ajaran_id' => 'Tahun Ajaran',
            'nama' => 'Nama Periode',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
        ]);
    }
}
