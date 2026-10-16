<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Support\Toast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class KartuController extends Controller
{
    public function index(): Response
    {
        // Hanya kelas yang punya ujian resmi (terbit)
        $kelasIds = \App\Models\Ujian::query()
            ->where('status', 'terbit')
            ->whereIn('kategori', \App\Models\Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas')
            ->with('guruKelas:id,kelas_id')
            ->get()
            ->pluck('guruKelas.kelas_id')
            ->unique()
            ->values();

        $kelass = Kelas::query()
            ->whereIn('id', $kelasIds)
            ->withCount(['siswas as jumlah_siswa' => fn ($q) => $q->where('siswa_kelas.active', true)])
            ->orderBy('nama')
            ->get();

        $dataKelas = $kelass->map(function ($k) {
            $nisns = SiswaKelas::where('kelas_id', $k->id)->where('active', true)->pluck('siswa_nisn');
            $sudah = Siswa::whereIn('nisn', $nisns)->whereNotNull('password_ujian')->count();

            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'jumlah_siswa' => $k->jumlah_siswa,
                'sudah_password' => $sudah,
            ];
        });

        $total = $dataKelas->sum('jumlah_siswa');
        $sudah = $dataKelas->sum('sudah_password');

        return Inertia::render('panitia/Kartu/Index', [
            'kelass' => $dataKelas,
            'statistik' => [
                'total' => $total,
                'sudah' => $sudah,
                'belum' => $total - $sudah,
            ],
        ]);
    }

    public function generate(Request $request, ?int $kelasId = null): \Illuminate\Http\RedirectResponse
    {
        // Hanya untuk kelas yang punya ujian resmi (terbit)
        $kelasIdsResmi = \App\Models\Ujian::query()
            ->where('status', 'terbit')
            ->whereIn('kategori', \App\Models\Ujian::KATEGORI_BESAR)
            ->whereHas('guruKelas')
            ->with('guruKelas:id,kelas_id')
            ->get()
            ->pluck('guruKelas.kelas_id')
            ->unique();

        $query = SiswaKelas::where('active', true)->whereIn('kelas_id', $kelasIdsResmi);
        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }
        $nisns = $query->pluck('siswa_nisn')->unique();

        $dibuat = 0;
        foreach ($nisns as $nisn) {
            $siswa = Siswa::where('nisn', $nisn)->first();
            if ($siswa && ! $siswa->password_ujian) {
                $plain = strtoupper(Str::random(6));
                $plain = strtr($plain, ['O' => 'A', '0' => 'B', 'I' => 'C', '1' => 'D']);
                // Pakai query builder langsung (Eloquent update bermasalah)
                \Illuminate\Support\Facades\DB::table('siswa')
                    ->where('nisn', $nisn)
                    ->update(['password_ujian' => Crypt::encryptString($plain)]);
                $dibuat++;
            }
        }

        Toast::success("{$dibuat} password berhasil dibuat.");

        return Redirect::back();
    }
}
