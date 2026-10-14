---
paths:
  - 'app/Http/Controllers/{Guru,Siswa,Admin}/*Ujian*.php,app/Services/UjianService.php,app/Models/Ujian.php'
---

# Models

## Modul CBT (Ujian): arsitektur & aturan
Modul Ujian/CBT mengikuti pola Tugas: berlabuh pada guru_kelas, tiap ujian membuat 1 baris Penilaian (sumber = kategori: kuis/uts/uas/usbk, tipe='cbt') dan sinkron nilai_total ke detail_penilaian via UjianService::syncPenilaian (updateOrCreate, guru_id = guru pengampu).
- 4 kategori satu tabel `ujians` + kolom `kategori`. Kategori besar (uts/uas/usbk) WAJIB dijadwalkan di dalam window PeriodeUjian admin (validasi di controller store/update). Kuis bebas.
- 5 tipe soal: pg, multi, benar_salah, isian (auto-grade), esai (manual). Auto-grade objektif saat submit; esai bikin nilai_total null sampai dikoreksi guru, baru sinkron ke penilaian.
- Otorisasi: guru scoped ke guru_id sendiri (abort 404 lintas guru); admin (Controller base, bukan BaseAppController) akses penuh lintas guru, pilih penugasan via dropdown, set dibuat_oleh_admin=true.
- Token 6 char (UjianService::generateToken, tanpa O/0/I/1), harus di-rilis (token_released_at) sebelum siswa bisa mulai; validasi token & batas_at & maks_attempt ditegakkan SERVER di Siswa\UjianController.
- Proctoring browser (fullscreen, blur, copy/paste, contextmenu) dilaporkan ke endpoint lapor -> PelanggaranLog + auto-diskualifikasi di maks_pelanggaran. Bukan lockdown OS.
- Migrasi widen_sumber_columns_for_cbt mengubah penilaian.sumber & detail_penilaian.sumber dari enum ke string(20) supaya menampung kategori CBT.
- Wayfinder: nama fungsi action = nama METHOD controller (mis. simpanJawaban, nilaiEsai), bukan nama route. Regenerate setelah tambah route.
