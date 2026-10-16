<?php

namespace App\Services;

use App\Models\GuruKelas;
use App\Models\Jadwal;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

/**
 * Sync jadwal baru (cbt_jadwals) ke sistem ujian lama (cbt_ujians)
 * agar siswa bisa melihat dan mengerjakan ujian.
 *
 * Satu Ujian dibuat per kelas (karena Ujian lama terikat satu guru_kelas_id).
 */
class JadwalPublishService
{
    /**
     * Terbitkan jadwal: buat Ujian + salin soal + buat sesi per kelas.
     *
     * @return array{ujian_ids: int[], jumlah_soal: int, jumlah_sesi: int}
     */
    public function terbitkan(Jadwal $jadwal): array
    {
        $jadwal->load(['kelas', 'banksoal.soals', 'sesi', 'event']);

        return DB::transaction(function () use ($jadwal) {
            $ujianIds = [];
            $totalSoal = 0;
            $totalSesi = 0;

            $kategori = $this->kategoriDariEvent($jadwal->event?->nama);

            foreach ($jadwal->kelas as $kelas) {
                // Cari guru_kelas untuk kelas ini (ambil yang pertama)
                $guruKelas = GuruKelas::where('kelas_id', $kelas->id)->first();

                // Buat Ujian
                $ujian = Ujian::create([
                    'guru_kelas_id' => $guruKelas?->id,
                    'guru_id' => $guruKelas?->guru_id,
                    'dibuat_oleh_admin' => true,
                    'kategori' => $kategori,
                    'judul' => $jadwal->nama.' - '.$kelas->nama,
                    'tanggal_mulai' => $jadwal->tanggal,
                    'tanggal_selesai' => $jadwal->tanggal,
                    'durasi_menit' => $jadwal->durasi_menit,
                    'acak_soal' => (bool) $jadwal->acak_soal,
                    'acak_opsi' => (bool) $jadwal->acak_opsi,
                    'tampilkan_hasil' => (bool) $jadwal->tampilkan_nilai,
                    'status' => 'terbit',
                    'event_id' => $jadwal->event_id,
                    'kelompok_bank_soal_id' => $jadwal->kelompok_bank_soal_id,
                ]);

                $ujianIds[] = $ujian->id;

                // Salin soal dari bank soal
                $urutan = 1;
                foreach ($jadwal->banksoal->soals as $bankSoal) {
                    Soal::create([
                        'ujian_id' => $ujian->id,
                        'bank_soal_id' => $bankSoal->id,
                        'tipe' => $bankSoal->tipe,
                        'pertanyaan' => $bankSoal->pertanyaan,
                        'poin' => $bankSoal->poin,
                        'urutan' => $urutan++,
                        'kunci_isian' => $bankSoal->kunci_isian,
                        'isian_case_sensitive' => $bankSoal->isian_case_sensitive,
                        'kode_kd' => $bankSoal->kode_kd,
                        'indikator' => $bankSoal->indikator,
                        'media_path' => $bankSoal->media_path,
                        'media_type' => $bankSoal->media_type,
                        'detail' => $bankSoal->detail,
                    ]);
                    $totalSoal++;
                }

                // Buat sesi dari JadwalSesi
                foreach ($jadwal->sesi as $jadwalSesi) {
                    SesiUjian::create([
                        'ujian_id' => $ujian->id,
                        'nama' => $jadwalSesi->nama.' ('.$kelas->nama.')',
                        'urutan' => $jadwalSesi->urutan,
                        'waktu_mulai' => $jadwal->tanggal->format('Y-m-d').' '.$jadwalSesi->jam_mulai,
                        'waktu_selesai' => $jadwal->tanggal->format('Y-m-d').' '.$jadwalSesi->jam_selesai,
                        'kapasitas' => $jadwalSesi->kapasitas,
                        'terisi' => 0,
                        'ruang_ujian_id' => $jadwalSesi->ruang_ujian_id,
                        'token' => $jadwalSesi->token,
                        'token_released_at' => $jadwalSesi->token_released_at,
                    ]);
                    $totalSesi++;
                }
            }

            $jadwal->update(['status' => 'published']);

            return [
                'ujian_ids' => $ujianIds,
                'jumlah_soal' => $totalSoal,
                'jumlah_sesi' => $totalSesi,
            ];
        });
    }

    /**
     * Tentukan kategori ujian dari nama event.
     */
    private function kategoriDariEvent(?string $namaEvent): string
    {
        $nama = strtolower($namaEvent ?? '');

        if (str_contains($nama, 'uas')) {
            return 'uas';
        }
        if (str_contains($nama, 'usbk') || str_contains($nama, 'usb')) {
            return 'usbk';
        }
        if (str_contains($nama, 'uts')) {
            return 'uts';
        }

        return 'uts';
    }
}
