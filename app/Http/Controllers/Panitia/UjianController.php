<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\BaseAppController;
use App\Models\BeritaAcaraUjian;
use App\Models\Event;
use App\Models\Group;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JadwalSesi;
use App\Models\Kelas;
use App\Models\KelompokBankSoal;
use App\Models\RuangUjian;
use App\Models\SesiUjian;
use App\Models\SesiUjianPeserta;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use App\Services\JadwalPublishService;
use App\Services\KelengkapanUjianService;
use App\Services\NotifikasiService;
use App\Services\SesiAssignService;
use App\Services\UjianService;
use App\Support\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CBT v2: Panitia Ujian — kelola ujian resmi (UTS/UAS/USBK) lintas kelas.
 *
 * Panitia punya akses penuh: paket soal, sesi, ruang, token, dokumen,
 * dan monitoring real-time. Guru tetap kelola ujiannya sendiri.
 */
class UjianController extends BaseAppController
{
    public function __construct(
        private readonly KelengkapanUjianService $kelengkapanService,
    ) {}










    private function pesertaSesi(Ujian $ujian, SesiUjian $sesi): Collection
    {
        $kelasId = $ujian->guruKelas?->kelas_id;

        $query = Siswa::query()->orderBy('nama_lengkap');

        if ($ujian->sesiTerjadwal()) {
            $nisns = $sesi->pesertas()->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } elseif ($kelasId !== null) {
            $nisns = SiswaKelas::where('kelas_id', $kelasId)
                ->where('active', true)->pluck('siswa_nisn');
            $query->whereIn('nisn', $nisns);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }
}
