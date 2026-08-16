<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use Illuminate\Http\Request;

class GuruKelasController extends Controller
{
    public function __invoke(Guru $guru)
    {
        $kelasIds = GuruKelas::whereGuruId($guru->id)->distinct()->pluck('kelas_id')->values();
        $kelasSaya = Kelas::with([
            'guruKelas' => function ($q) use ($guru) {
                $q->where('guru_id', $guru->id);
            },
            'guruKelas.matpel',
        ])
            ->findMany($kelasIds)
            ->transform(function (Kelas $kelas) {
                return [
                    'nama_kelas' => $kelas->nama,
                    'matpels' => $kelas->guruKelas->map(fn($e) => [
                        'nama' => $e->matpel->name ?? null,
                    ]),
                ];
            });

        return inertia('admin/AturGuruKelas/Index', [
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            'kelas' => $kelasSaya,
        ]);
    }
}
