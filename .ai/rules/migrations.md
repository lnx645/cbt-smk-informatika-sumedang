---
paths:
  - 'database/migrations/**'
---

# Migrations

## FK ke tabel siswa memakai nisn varchar(10), bukan id
Tabel siswa (singular, bukan siswas) ber-PK `nisn` varchar(10). `foreignIdFor(Siswa::class)` menghasilkan bigint dan gagal; pakai `$table->string('siswa_nisn', 10)` + `$table->foreign('siswa_nisn')->references('nisn')->on('siswa')` (contoh: tugas_pengumpulans).
