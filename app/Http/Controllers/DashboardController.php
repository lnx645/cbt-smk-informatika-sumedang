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
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {

        if ($this->is_guru) {
            $dateNow = Carbon::now()->getTranslatedDayName();
            $jadwal = JadwalPelajaran::whereGuruId($this->user->guru->id)
                ->whereHari($dateNow)
                ->with("matpel")
                ->with("kelas")
                ->with("jamPelajaran")->paginate(100)->through(function (JadwalPelajaran $e) {
                    $berlangsung = Carbon::now()
                        ->between(Carbon::parse($e->jam_mulai), Carbon::parse($e->jam_selesai));
                    $akan_datang = Carbon::now()
                        ->lt(Carbon::parse($e->jam_mulai));
                    return [
                        "id" => $e->id,
                        "hari" => $e->jamPelajaran->hari,
                        "mulai" => $e->jam_mulai,
                        "selesai" => $e->jam_selesai,
                        "berlangsung" => $berlangsung,
                        "akan_datang" => $akan_datang,
                        "sudah_selesai" => Carbon::now()->gt($e->jam_selesai),
                        "jam_ke" => $e->jamPelajaran->label,
                        "matpel" => $e->matpel->name,
                        "kelas" => $e->kelas->nama,
                        "total_siswa" => SiswaKelas::whereActive(true)->with(["siswa"])->select(["siswa_nisn", "kelas_id"])->whereKelasId($e->kelas_id)->get()->count(),
                    ];
                })->values();
            return inertia("guru/Index", [
                "jadwal_saya" => $jadwal,
                "tanggal_sekarang" => Carbon::now()->translatedFormat("D, d-M-Y")
            ]);
        }

        return Inertia::render('Dashboard', []);

    }
}
