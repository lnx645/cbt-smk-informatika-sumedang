<?php

namespace App\Services;

use App\Models\Ujian;

/**
 * CBT v2: validasi kelengkapan ujian resmi sebelum terbit.
 *
 * Ujian kategori besar (uts/uas/usbk) wajib memenuhi checklist:
 * paket soal, sesi + ruang, token aktif, tata tertib, dan KKM.
 * Kuis/UH (mode santai) hanya butuh minimal 1 soal.
 */
class KelengkapanUjianService
{
    /**
     * @return array<string> daftar masalah; kosong bila lengkap.
     */
    public function cek(Ujian $ujian): array
    {
        $masalah = [];

        // Mode santai: cukup ada soal.
        if (! $ujian->kategoriBesar()) {
            $jumlahSoal = $ujian->memakaiPaketSoal()
                ? $ujian->paketSoals()->withCount('soals')->get()->sum('soals_count')
                : $ujian->soals()->whereNull('paket_soal_id')->count();

            if ($jumlahSoal === 0) {
                $masalah[] = 'Tambahkan minimal satu soal sebelum menerbitkan.';
            }

            return $masalah;
        }

        // Mode resmi: checklist ketat.
        if (! $ujian->memakaiPaketSoal()) {
            $masalah[] = 'Buat paket soal (minimal 2 paket, misal A dan B).';
        } else {
            $paketKosong = $ujian->paketSoals()->withCount('soals')->get()->filter(fn ($p) => $p->soals_count === 0);
            if ($paketKosong->isNotEmpty()) {
                $masalah[] = 'Paket '.$paketKosong->pluck('kode')->join(', ').' belum ada soalnya.';
            }
        }

        if (! $ujian->memakaiSesi()) {
            $masalah[] = 'Atur sesi ujian (gelombang) beserta waktunya.';
        } else {
            $tanpaRuang = $ujian->sesis()->whereNull('ruang_ujian_id')->count();
            if ($tanpaRuang > 0) {
                $masalah[] = "{$tanpaRuang} sesi belum ditentukan ruangnya.";
            }
        }

        if ($ujian->token === null) {
            $masalah[] = 'Aktifkan token ujian.';
        }

        if (empty(trim((string) $ujian->tata_tertib))) {
            $masalah[] = 'Isi tata tertib ujian.';
        }

        if ($ujian->kkm === null) {
            $masalah[] = 'Isi KKM (untuk penentuan remedial).';
        }

        return $masalah;
    }

    public function lengkap(Ujian $ujian): bool
    {
        return $this->cek($ujian) === [];
    }
}
