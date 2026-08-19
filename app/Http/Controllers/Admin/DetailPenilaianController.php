<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPenilaian;
use App\Models\Kelas;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DetailPenilaianController extends Controller
{
    /**
     * Show filter page to select class for a given penilaian.
     */
    public function filterSiswa(Request $request, Penilaian $penilaian): Response
    {
        $kelas = Kelas::leaf()->orderBy('nama')->get(['id', 'nama']);

        $siswas = null;
        $selectedKelasId = $request->query('kelas_id');

        if ($selectedKelasId !== null && $selectedKelasId !== '') {
            $siswas = SiswaKelas::query()
                ->where('kelas_id', $selectedKelasId)
                ->where('active', true)
                ->orderBy('siswa_nisn')
                ->with('siswa')
                ->get()
                ->map(fn ($sk): array => [
                    'nisn' => $sk->siswa->nisn,
                    'nama' => $sk->siswa->nama_lengkap,
                ])
                ->all();
        }

        return Inertia::render('admin/DetailPenilaian/Filter', [
            'penilaian' => $penilaian,
            'kelas' => $kelas,
            'selectedKelasId' => $selectedKelasId,
            'siswas' => $siswas,
        ]);
    }

    /**
     * Show detail nilai for a specific siswa within a penilaian.
     */
    public function detail(Request $request, Penilaian $penilaian, Siswa $siswa): Response
    {
        $detail = DetailPenilaian::firstOrCreate(
            [
                'penilaian_id' => $penilaian->id,
                'siswa_nisn' => $siswa->nisn,
            ],
            [
                'nilai' => 0,
                'sumber' => 'manual',
            ],
        );

        return Inertia::render('admin/DetailPenilaian/Show', [
            'penilaian' => $penilaian,
            'siswa' => $siswa,
            'detail' => $detail,
        ]);
    }

    /**
     * Store (or update) nilai for a siswa.
     */
    public function storeNilai(Request $request, Penilaian $penilaian, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'nilai' => ['required', 'numeric', 'min:0'],
            'sumber' => ['required', 'in:manual,tugas,cbt'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DetailPenilaian::updateOrCreate(
            [
                'penilaian_id' => $penilaian->id,
                'siswa_nisn' => $siswa->nisn,
            ],
            $data
        );

        Toast::success('Nilai berhasil disimpan.');

        return Redirect::back();
    }

    /**
     * Update existing nilai for a siswa.
     */
    public function updateNilai(Request $request, Penilaian $penilaian, Siswa $siswa): RedirectResponse
    {
        $data = $request->validate([
            'nilai' => ['required', 'numeric', 'min:0'],
            'sumber' => ['required', 'in:manual,tugas,cbt'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $detail = DetailPenilaian::where('penilaian_id', $penilaian->id)
            ->where('siswa_nisn', $siswa->nisn)
            ->firstOrFail();

        $detail->update($data);
        Toast::success('Nilai berhasil diperbarui.');

        return Redirect::back();
    }
}
