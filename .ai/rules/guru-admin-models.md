---
paths:
  - 'app/Http/Controllers/{Guru,Admin}/UjianController.php,app/Models/Ujian.php'
---

# Guru Admin Models

## Pengelola token ujian (guru vs admin)
Kolom `ujians.token_pengelola` ('guru'|'admin', default 'guru') menentukan siapa yang boleh buat & rilis token.
- Guru\UjianController::generateToken/toggleToken menolak (Toast::error) jika token_pengelola !== 'guru'.
- Admin\UjianController punya setPengelolaToken (ubah pengelola), generateToken, toggleToken — semua menolak jika token_pengelola !== 'admin'.
- Guru & admin form validasi `token_pengelola` via Rule::in(['guru','admin']) (sometimes).
- Frontend: guru Index menonaktifkan tombol token & tampil badge "oleh admin" saat didelegasikan; admin Index punya dropdown pengelola + kontrol token saat pengelola='admin'.
- Token tetap: 6 char (UjianService::generateToken), harus token_released_at != null sebelum siswa bisa mulai, validasi di Siswa\UjianController.
