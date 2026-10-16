<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\JadwalSesi;
use App\Models\RuangUjian;
use App\Models\SesiUjian;
use App\Models\SiswaKelas;
use App\Models\Ujian;
use Illuminate\Support\Collection;

class SesiAssignService
{
    /**
     * Cari sesi tersedia untuk siswa (cek kapasitas otomatis).
     * Jika sesi penuh, cari sesi berikutnya yang masih ada slot.
     */
    public function cariSesiTersedia(Ujian $ujian, ?int $prioritasSesiId = null): ?SesiUjian
    {
        $query = $ujian->sesi()->orderBy('urutan');

        // Prioritaskan sesi tertentu jika diminta
        if ($prioritasSesiId) {
            $prioritas = $query->clone()->find($prioritasSesiId);
            if ($prioritas && $prioritas->terisi < $prioritas->kapasitas) {
                return $prioritas;
            }
        }

        // Cari sesi pertama yang masih ada slot
        return $query->get()->first(
            fn (SesiUjian $s) => $s->terisi < $s->kapasitas
        );
    }

    /**
     * Tambah hitungan terisi saat siswa masuk sesi.
     */
    public function tambahTerisi(SesiUjian $sesi): void
    {
        $sesi->increment('terisi');
    }

    /**
     * Kurangi hitungan terisi saat sesi direset.
     */
    public function kurangiTerisi(SesiUjian $sesi): void
    {
        if ($sesi->terisi > 0) {
            $sesi->decrement('terisi');
        }
    }

    /**
     * Hitung berapa sesi yang dibutuhkan untuk menampung semua siswa.
     *
     * Contoh: 120 siswa dengan kapasitas ruangan 40 → 3 sesi.
     */
    public function hitungSesiDibutuhkan(int $jumlahSiswa, int $kapasitasRuangan): int
    {
        if ($kapasitasRuangan <= 0) {
            $kapasitasRuangan = 40;
        }

        if ($jumlahSiswa <= 0) {
            return 1;
        }

        return (int) ceil($jumlahSiswa / $kapasitasRuangan);
    }

    /**
     * Hitung total siswa aktif dari daftar kelas (relasi siswa_kelas, active = true).
     *
     * @param  array<int>  $daftarKelasIds
     */
    public function hitungTotalSiswa(array $daftarKelasIds): int
    {
        if ($daftarKelasIds === []) {
            return 0;
        }

        return SiswaKelas::whereIn('kelas_id', $daftarKelasIds)
            ->where('active', true)
            ->distinct('siswa_nisn')
            ->count('siswa_nisn');
    }

    /**
     * Ambil kapasitas dari ruangan; null berarti daring → pakai default.
     */
    public function kapasitasRuangan(?int $ruangUjianId, int $default = 40): int
    {
        if ($ruangUjianId === null) {
            return $default;
        }

        $ruang = RuangUjian::find($ruangUjianId);

        return $ruang !== null ? (int) $ruang->kapasitas : $default;
    }

    /**
     * Bagi siswa ke sesi secara otomatis untuk satu jadwal.
     *
     * Sesi dibuat berurutan: tiap sesi mulai setelah sesi sebelumnya
     * selesai ditambah jeda. Sesi lama milik jadwal ini dihapus dulu
     * agar tidak dobel.
     *
     * @param  array<int>  $daftarKelasIds
     * @return Collection<int, JadwalSesi> Sesi yang dibuat.
     */
    public function bagiSiswaKeSesi(
        int $jadwalId,
        array $daftarKelasIds,
        string $jamAwal = '08:00',
        int $jedaMenit = 30,
        ?int $ruangUjianId = null,
        int $kapasitasDaring = 40,
    ): Collection {
        $jadwal = Jadwal::findOrFail($jadwalId);
        $totalSiswa = $this->hitungTotalSiswa($daftarKelasIds);
        $kapasitas = $this->kapasitasRuangan($ruangUjianId, $kapasitasDaring);
        $jumlahSesi = $this->hitungSesiDibutuhkan($totalSiswa, $kapasitas);

        // Hapus sesi lama agar pembagian ulang tidak dobel
        $jadwal->sesi()->delete();

        $durasi = (int) $jadwal->durasi_menit;
        [$jam, $menit] = array_map(intval(...), explode(':', $jamAwal));
        $menitAwal = $jam * 60 + $menit;

        $sesiDibuat = collect();

        for ($i = 1; $i <= $jumlahSesi; $i++) {
            $mulai = $menitAwal + ($i - 1) * ($durasi + $jedaMenit);
            $selesai = $mulai + $durasi;

            $sesiDibuat->push(JadwalSesi::create([
                'jadwal_id' => $jadwal->id,
                'nama' => 'Sesi '.$i,
                'jam_mulai' => $this->menitKeJam($mulai),
                'jam_selesai' => $this->menitKeJam($selesai),
                'ruang_ujian_id' => $ruangUjianId,
                'kapasitas' => $kapasitas,
                'terisi' => 0,
            ]));
        }

        return $sesiDibuat;
    }

    /**
     * Ubah menit-sejak-tengah-malam menjadi string jam "HH:MM:SS".
     */
    protected function menitKeJam(int $totalMenit): string
    {
        return sprintf('%02d:%02d:00', intdiv($totalMenit, 60) % 24, $totalMenit % 60);
    }
}
