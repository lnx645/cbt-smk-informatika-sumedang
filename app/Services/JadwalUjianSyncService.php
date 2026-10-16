<?php

namespace App\Services;

use App\Models\GuruKelas;
use App\Models\JadwalUjian;
use App\Models\SesiUjian;
use App\Models\Soal;
use App\Models\Ujian;
use Illuminate\Support\Facades\DB;

/**
 * Sync JadwalUjian ke sistem Ujian agar siswa bisa mengerjakan.
 * Satu Ujian dibuat per kelas.
 */
class JadwalUjianSyncService
{
    public function sync(JadwalUjian $jadwalUjian): array
    {
        $jadwalUjian->load(['kelas', 'banksoal.soals', 'sesi', 'event']);

        return DB::transaction(function () use ($jadwalUjian) {
            $ujianIds = [];
            $totalSoal = 0;
            $totalSesi = 0;
            $kategori = $this->kategoriDariEvent($jadwalUjian->event?->nama);

            foreach ($jadwalUjian->kelas as $kelas) {
                $guruKelas = GuruKelas::where('kelas_id', $kelas->id)->first();

                $ujian = Ujian::create([
                    'guru_kelas_id' => $guruKelas?->id,
                    'guru_id' => $guruKelas?->guru_id,
                    'dibuat_oleh_admin' => true,
                    'kategori' => $kategori,
                    'judul' => $jadwalUjian->nama.' - '.$kelas->nama,
                    'tanggal_mulai' => $jadwalUjian->tanggal,
                    'tanggal_selesai' => $jadwalUjian->tanggal,
                    'durasi_menit' => $jadwalUjian->durasi_menit,
                    'acak_soal' => $jadwalUjian->acak_soal,
                    'acak_opsi' => $jadwalUjian->acak_opsi,
                    'tampilkan_hasil' => $jadwalUjian->tampilkan_nilai,
                    'status' => 'terbit',
                    'event_id' => $jadwalUjian->event_id,
                    'kelompok_bank_soal_id' => $jadwalUjian->kelompok_bank_soal_id,
                ]);

                $ujianIds[] = $ujian->id;

                $urutan = 1;
                foreach ($jadwalUjian->banksoal->soals as $bankSoal) {
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

                foreach ($jadwalUjian->sesi as $js) {
                    SesiUjian::create([
                        'ujian_id' => $ujian->id,
                        'nama' => $js->nama.' ('.$kelas->nama.')',
                        'urutan' => $js->urutan,
                        'waktu_mulai' => $jadwalUjian->tanggal->format('Y-m-d').' '.$js->jam_mulai,
                        'waktu_selesai' => $jadwalUjian->tanggal->format('Y-m-d').' '.$js->jam_selesai,
                        'kapasitas' => $js->kapasitas,
                        'terisi' => 0,
                        'ruang_ujian_id' => $js->ruang_ujian_id,
                        'token' => $js->token,
                        'token_released_at' => $js->token_released_at,
                    ]);
                    $totalSesi++;
                }
            }

            $jadwalUjian->update(['status' => 'published']);

            return [
                'ujian_ids' => $ujianIds,
                'jumlah_soal' => $totalSoal,
                'jumlah_sesi' => $totalSesi,
            ];
        });
    }

    private function kategoriDariEvent(?string $nama): string
    {
        $n = strtolower($nama ?? '');
        if (str_contains($n, 'uas')) {
            return 'uas';
        }
        if (str_contains($n, 'usbk') || str_contains($n, 'usb')) {
            return 'usbk';
        }

        return 'uts';
    }
}
