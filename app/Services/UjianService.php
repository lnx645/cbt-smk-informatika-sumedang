<?php

namespace App\Services;

use App\Models\DetailPenilaian;
use App\Models\JawabanSiswa;
use App\Models\Soal;
use App\Models\Ujian;
use App\Models\UjianPengerjaan;
use Illuminate\Support\Str;

class UjianService
{
    /**
     * Buat token acak 6 karakter (huruf/angka kapital tanpa karakter ambigu).
     */
    public function generateToken(): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa O/0/I/1
        $token = '';

        for ($i = 0; $i < 6; $i++) {
            $token .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $token;
    }

    /**
     * Nilai satu jawaban objektif. Mengembalikan true jika benar.
     */
    public function nilaiJawabanObjektif(Soal $soal, JawabanSiswa $jawaban): bool
    {
        return match ($soal->tipe) {
            'pg', 'benar_salah' => $this->nilaiPilihanTunggal($soal, $jawaban),
            'multi' => $this->nilaiMultiJawaban($soal, $jawaban),
            'isian' => $this->nilaiIsian($soal, $jawaban),
            default => false,
        };
    }

    private function nilaiPilihanTunggal(Soal $soal, JawabanSiswa $jawaban): bool
    {
        $dipilih = collect($jawaban->opsi_dipilih ?? [])->map(fn ($id): int => (int) $id);

        if ($dipilih->count() !== 1) {
            return false;
        }

        $benarIds = $soal->opsi->where('benar', true)->pluck('id')->map(fn ($id): int => (int) $id);

        return $benarIds->contains($dipilih->first());
    }

    private function nilaiMultiJawaban(Soal $soal, JawabanSiswa $jawaban): bool
    {
        $dipilih = collect($jawaban->opsi_dipilih ?? [])->map(fn ($id): int => (int) $id)->sort()->values();
        $benarIds = $soal->opsi->where('benar', true)->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values();

        if ($dipilih->isEmpty() || $benarIds->isEmpty()) {
            return false;
        }

        return $dipilih->all() === $benarIds->all();
    }

    private function nilaiIsian(Soal $soal, JawabanSiswa $jawaban): bool
    {
        $jawab = trim((string) $jawaban->jawaban_teks);

        if ($jawab === '') {
            return false;
        }

        foreach ($soal->kunci_isian ?? [] as $kunci) {
            $kunci = trim((string) $kunci);

            $cocok = $soal->isian_case_sensitive
                ? $jawab === $kunci
                : Str::lower($jawab) === Str::lower($kunci);

            if ($cocok) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nilai ulang semua jawaban objektif pada sebuah sesi pengerjaan.
     */
    public function gradeObjektif(UjianPengerjaan $pengerjaan): void
    {
        $pengerjaan->loadMissing(['jawabans.soal.opsi']);

        foreach ($pengerjaan->jawabans as $jawaban) {
            $soal = $jawaban->soal;

            if ($soal === null || ! $soal->objektif()) {
                continue;
            }

            $benar = $this->nilaiJawabanObjektif($soal, $jawaban);
            $jawaban->benar = $benar;
            $jawaban->skor = $benar ? $soal->poin : 0;
            $jawaban->save();
        }
    }

    /**
     * Hitung ulang nilai_objektif, nilai_esai, nilai_total dan sinkron ke penilaian
     * jika seluruh soal esai telah dinilai. Mengembalikan true jika sudah lengkap.
     */
    public function rekalkulasiNilai(UjianPengerjaan $pengerjaan): bool
    {
        $ujian = $pengerjaan->ujian;
        $ujian->loadMissing('soals');
        $pengerjaan->loadMissing(['jawabans.soal']);

        $totalPoin = max(1, (int) $ujian->soals->sum('poin'));

        $poinObjektif = 0.0;
        $poinEsai = 0.0;
        $esaiPending = false;

        // Soal esai yang ada pada ujian
        $soalEsaiIds = $ujian->soals->where('tipe', 'esai')->pluck('id');
        $jawabanBySoal = $pengerjaan->jawabans->keyBy('soal_id');

        foreach ($pengerjaan->jawabans as $jawaban) {
            $soal = $jawaban->soal;

            if ($soal === null) {
                continue;
            }

            if ($soal->objektif()) {
                $poinObjektif += (float) ($jawaban->skor ?? 0);
            } elseif ($soal->tipe === 'esai') {
                if ($jawaban->skor === null) {
                    $esaiPending = true;
                } else {
                    $poinEsai += (float) $jawaban->skor;
                }
            }
        }

        // Soal esai yang belum ada jawabannya tetap dianggap pending bila belum dinilai.
        foreach ($soalEsaiIds as $soalId) {
            if (! $jawabanBySoal->has($soalId) || $jawabanBySoal->get($soalId)->skor === null) {
                $esaiPending = true;
            }
        }

        $pengerjaan->nilai_objektif = round($poinObjektif / $totalPoin * $ujian->nilai_maks, 2);
        $pengerjaan->nilai_esai = $esaiPending ? null : round($poinEsai / $totalPoin * $ujian->nilai_maks, 2);

        if ($esaiPending) {
            $pengerjaan->nilai_total = null;
            $pengerjaan->save();

            return false;
        }

        $pengerjaan->nilai_total = round(($poinObjektif + $poinEsai) / $totalPoin * $ujian->nilai_maks, 2);
        $pengerjaan->save();

        $this->syncPenilaian($pengerjaan);

        return true;
    }

    /**
     * Sinkronkan nilai_total ke detail_penilaian (pola TugasController::nilai()).
     */
    public function syncPenilaian(UjianPengerjaan $pengerjaan): void
    {
        $ujian = $pengerjaan->ujian;

        if ($ujian->penilaian_id === null || $pengerjaan->nilai_total === null) {
            return;
        }

        DetailPenilaian::updateOrCreate(
            [
                'penilaian_id' => $ujian->penilaian_id,
                'guru_kelas_id' => $ujian->guru_kelas_id,
                'siswa_nisn' => $pengerjaan->siswa_nisn,
            ],
            [
                'tahun_ajaran_id' => $ujian->guruKelas->tahun_ajaran_id,
                'guru_id' => $ujian->guru_id,
                'nilai' => $pengerjaan->nilai_total,
                'sumber' => $ujian->kategori,
            ],
        );
    }

    /**
     * Default proctoring/atribut per kategori (dipakai saat membuat ujian).
     *
     * @return array<string, int|bool>
     */
    public function defaultKategori(string $kategori): array
    {
        return match ($kategori) {
            'usbk' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 3,
                'bobot' => 3,
            ],
            'uas' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 5,
                'bobot' => 3,
            ],
            'uts' => [
                'maks_attempt' => 1,
                'acak_soal' => true,
                'acak_opsi' => true,
                'wajib_fullscreen' => true,
                'maks_pelanggaran' => 5,
                'bobot' => 2,
            ],
            default => [ // kuis
                'maks_attempt' => 1,
                'acak_soal' => false,
                'acak_opsi' => false,
                'wajib_fullscreen' => false,
                'maks_pelanggaran' => 0,
                'bobot' => 1,
            ],
        };
    }
}
