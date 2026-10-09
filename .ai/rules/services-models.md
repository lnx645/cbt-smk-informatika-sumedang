---
paths:
  - 'app/Http/Controllers/{Guru,Admin}/BankSoalController.php,app/Services/BankSoalService.php,app/Models/BankSoal.php'
---

# Services Models

## Bank Soal: snapshot per-matpel
Bank Soal = template soal per matpel, dipakai bersama guru pengampu matpel sama; admin akses penuh lintas matpel.
- Tabel: bank_soals (matpel_id, guru_id nullable=pembuat, tipe, pertanyaan, poin, topik, kesulitan(mudah|sedang|sulit), kunci_isian json, isian_case_sensitive) + bank_opsi_soals. soals.bank_soal_id nullable = jejak asal (nullOnDelete).
- SNAPSHOT: BankSoalService::salinKeUjian menyalin bank->soals+opsi (bukan referensi); edit/hapus bank TIDAK mengubah ujian. simpanDariSoal = copy soal ujian->bank. generate = inRandomOrder limit per matpel+filter lalu salin.
- Guru scope: matpel dari GuruKelas aktif miliknya (Guru\BankSoalController::matpelIds); tolak 404/validasi bila di luar. Admin\BankSoalController tanpa scope, guru_id=null.
- SoalController (guru) punya ambilDariBank/simpanKeBank/generateDariBank; matpel diambil dari ujian->guruKelas->matpel_id; validasi bank_soal_ids harus exists where matpel_id.
- Validasi struktur soal & normalisasi kunci_isian sama persis dgn SoalController ujian (opsi nullable + cek di validateStruktur; kunci di-trim buang kosong). Frontend pakai form.transform untuk kirim opsi/kunci hanya sesuai tipe.
