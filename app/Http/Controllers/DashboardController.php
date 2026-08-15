<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Role;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseAppController
{
    private function _dashbaordGuru()
    {
        $dateNow = Carbon::now()->getTranslatedDayName();
        // $jadwal = JadwalPelajaran::whereGuruId($this->user->guru->id)
        //     ->with("matpel")
        //     ->with("kelas")
        //     ->with("jamPelajaran")->simplePaginate()
        //     ->through(function (JadwalPelajaran $e) {
        //         $berlangsung = Carbon::now()
        //             ->between(Carbon::parse($e->jam_mulai), Carbon::parse($e->jam_selesai));
        //         $akan_datang = Carbon::now()
        //             ->lt(Carbon::parse($e->jam_mulai));
        //         return [
        //             "id" => $e->id,
        //             "hari" => $e->jamPelajaran->hari,
        //             "mulai" => $e->jam_mulai,
        //             "kelas_id" => $e->kelas_id,
        //             "selesai" => $e->jam_selesai,
        //             "berlangsung" => $berlangsung,
        //             "akan_datang" => $akan_datang,
        //             "sudah_selesai" => Carbon::now()->gt($e->jam_selesai),
        //             "jam_ke" => $e->jamPelajaran->label,
        //             "matpel" => $e->matpel->name,
        //             "matpel_id" => $e->matpel_id,
        //             "kelas" => $e->kelas->nama,
        //             "total_siswa" => SiswaKelas::whereActive(true)->with(["siswa"])->select(["siswa_nisn", "kelas_id"])->whereKelasId($e->kelas_id)->get()->count(),
        //         ];
        //     })->values();

        $jadwl = JadwalPelajaran::whereGuruId($this->user->guru->id)->with([
            "matpel",
            "kelas"
        ])->distinct()->get()->map(function (JadwalPelajaran $jadwal) {
            return [
                "hari" => $jadwal->hari,
                "jam" => [
                    "mulai" => $jadwal->jam_mulai,
                    "selesai" => $jadwal->jam_selesai,
                ],
                "kelas"=>$jadwal->kelas->nama,
            ];
        })->values();
        dd($jadwl);

        return inertia("guru/Index", [
            "jadwal_saya" => Inertia::merge([]),
        ]);
    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {

        if ($this->is_guru) {
            return $this->_dashbaordGuru();
        }

        return Inertia::render('Dashboard', []);

    }
}
