<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\GuruKelas;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Support\Toast;
use Illuminate\Http\Request;

class GuruKelasController extends Controller
{
    public function store(Request $request, Guru $guru)
    {
        $data = $request->validate([
            "kelas_id" => ["required"],
            "matpel_id" => ["required"]
        ]);
        //disini cek dulu
        if (GuruKelas::where($data)->where("tahun_ajaran_id", $this->tahunAjaran->id)->exists()) {
            Toast::error("Opps mata pelajaran sudah ada! Atau sudah di tugaskan ke guru lain!");
        } else {
            $guru->guruKelas()->create([
                "kelas_id" => $data["kelas_id"],
                "matpel_id" => $data["matpel_id"],
                "tahun_ajaran_id" => $this->tahunAjaran->id,
                "aktif" => true,
                "kode_undangan" => uniqid()
            ]);
        }


    }
    public function __invoke(Guru $guru)
    {
        $ta = $this->tahunAjaran->id;
        $kelasIds = GuruKelas::where('guru_id', "=", $guru->id)
            ->where('tahun_ajaran_id', "=", $ta)
            ->distinct()
            ->pluck('kelas_id');
        $kelasSaya = Kelas::whereIn('id', $kelasIds)
            ->with([
                'guruKelas' => function ($q) use ($guru, $ta) {
                    $q->where('guru_id', $guru->id)
                        ->where('tahun_ajaran_id', $ta);
                },
                'guruKelas.matpel',
            ])
            ->get()
            ->map(fn($kelas) => [
                "id" => $kelas->id,
                'nama_kelas' => $kelas->nama,
                'matpels' => $kelas->guruKelas->map(fn($e) => [
                    'nama' => $e->matpel->name ?? null,
                ]),
            ]);
        $matpels = Matpel::select('id', 'name')->get()->map(fn($e) => [
            "name" => $e->name,
            "id" => $e->id
        ]);
        $kelas = Kelas::has("parent")
            ->where('active', true)
            ->get()
            ->map(fn($e) => [
                "name" => $e->nama,
                "id" => $e->id,
            ]);

        return inertia('admin/AturGuruKelas/Index', [
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            "guru_id" => $guru->id,
            'kelas' => $kelasSaya,
            "kelas_list" => $kelas,
            "matpels" => $matpels,
        ]);
    }


}
