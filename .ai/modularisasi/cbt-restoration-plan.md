# Rencana Restorasi CBT dari Git History — Agen 3

> **Status dokumen:** READ-ONLY audit + dokumen rencana. Tidak ada kode yang dipindah/
> direstore, tidak ada commit/push, tidak ada akses VPS, tidak ada checkout file lama.
> Branch saat audit: `fix-cbt-v2` (tidak pindah branch). Tanggal audit: 2026-10-16.
> Install package `nwidart/laravel-modules` sedang TERBLOKIR di gate plugin Composer —
> dokumen ini adalah rencana presisi untuk Agen Eksekutor setelah gate unblock.

---

## 1. Peta commit penghapusan (9 commit, 4 di antaranya menghapus file utuh)

| # | SHA | Tanggal | Pesan | Efek |
|---|-----|---------|-------|------|
| 1 | `4fb281e` | 2026-10-16 | Hapus fitur ujian resmi (jadwal baru) | 16 file dihapus (sistem jadwal gen-1 `cbt_jadwals`, Panitia UjianController 999 baris, Ruang panitia, service publish/sesi, 3 migrasi, 4 view jadwal, edit routes + UserLayout) |
| 2 | `95c4a84` | 2026-10-16 | Hapus halaman /ujian | 6 file dihapus (UjianLoginController, UjianModeController, 4 view `ujian/*`), 16 baris route dihapus |
| 3 | `ae60d08` | 2026-10-16 | Hapus komposisi soal dari form bank soal | 31 baris dihapus dari `guru/BankSoal/Kelompok.svelte` (file tetap ada) — **sudah di-supersede**: fitur komposisi sudah ditambahkan kembali di HEAD (`grep komposisi` = 5 hasil). **Tidak perlu direstore.** |
| 4 | `ee2b92a` | 2026-10-16 | Hapus fitur Ujian CBT di admin | 13 file dihapus (4 controller admin, 7 view admin, 1 migrasi periode_ujians), 42 baris `routes/admin.php` + 25 baris AdminLayout dihapus |
| 5 | `6bbf814` | 2026-10-16 | Bersihkan semua fitur ujian non-guru | 21 file dihapus (5 controller panitia, 3 model, 1 service, 1 migrasi, 11 view panitia/siswa), edit routes + UserLayout |
| 6 | `67c679b` | 2026-10-16 | Bersihkan sisa routes panitia | Hanya hapus 34 baris route panitia-ujian CBT v2 di `routes/web.php` (file tetap ada) |
| 7 | `6fd941c` | 2026-10-16 | Hapus kartu ujian siswa | Hanya hapus 1 baris route `siswa.ujian.kartu` di `routes/web.php` |
| 8 | `80b01b6` | 2026-10-16 | Hapus kartu peserta guru | Hanya hapus 1 baris route `guru.ujian.sesi.kartu` di `routes/web.php` |
| 9 | `56fa0bd` | 2026-10-16 | Bersihkan sisa import dan komentar | Hanya hapus 4 baris import/komentar di `routes/web.php` + `routes/admin.php` |

**Total: 56 file unik dihapus utuh** (Panitia/UjianController terhapus 2× dalam 2 generasi
berbeda — gen-1 999 baris di `4fb281e`, gen-2 78 baris di `6bbf814` — keduanya tercatat
terpisah di tabel inventaris).

### Fakta penting kondisi HEAD (yang MEMUDAHKAN restorasi)

- `app/Http/Controllers/Siswa/UjianController.php` **masih ada** di HEAD beserta
  9 route `siswa.ujian.*` di `routes/web.php`, TAPI 4 view yang direndernya sudah
  dihapus — controller ini merender `siswa/Ujian/Index|Konfirmasi|K erjakan|Hasil`
  **dan juga** `ujian/Konfirmasi` / `ujian/Hasil` (mode ujian). Kedua alur rusak
  hanya karena view-nya hilang → restore view = alur hidup kembali.
- `app/Http/Middleware/UjianAuthMiddleware.php` **masih ada** (guard sesi `/ujian`).
- Model pendukung alur ujian **masih ada**: `Ujian`, `UjianPengerjaan`,
  `SesiUjian`, `SesiUjianPeserta`, `SesiPengawas`, `JawabanSiswa`,
  `BeritaAcaraUjian`, `PelanggaranLog`, `PeriodeUjian`, `RuangUjian`,
  `Ujian::KATEGORI_BESAR = ['uts','uas','usbk']`, `Siswa::$fillable` masih memuat
  `password_ujian`.
- Dependensi `KartuController` (Kartu peserta) masih kompatibel dengan model HEAD.

---

## 2. Inventaris deduplicated (56 file) + penilaian RESTORE-CANDIDATE / SKIP

Legenda: **CANDIDATE** = layak direstore verbatim (adaptasi namespace/route saja) ke
`Modules/CBT`. **SKIP** = jangan restore verbatim; jadikan referensi desain untuk
tulis-ulang bersih di modul. **REF** = hanya ambil skema/ide, bukan kodenya.

### (a) Login & sesi ujian siswa — `/ujian/*` (dihapus di `95c4a84`, SOT = `d0d025f`)

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 1 | `app/Http/Controllers/UjianLoginController.php` (83) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Login UNBK-style NISN + `password_ujian` (Crypt, hash_equals); sudah diaudit & di-fix (varchar→text, error clear setelah regenerasi) |
| 2 | `app/Http/Controllers/UjianModeController.php` (73) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Daftar ujian mode-ujian; pasangan controller #1, dijaga `UjianAuthMiddleware` (masih ada) |
| 3 | `resources/js/pages/ujian/Login.svelte` (324) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | UI login ujian terpisah; sudah live-test |
| 4 | `resources/js/pages/ujian/Daftar.svelte` (265) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Daftar ujian tanpa dashboard; catatan: audit E2E daftar→konfirmasi sempat 404 (`/ujian/konfirmasi/35`) — eksekutor wajib re-verifikasi |
| 5 | `resources/js/pages/ujian/Konfirmasi.svelte` (228) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Tata tertib + info ujian pra-mulai (mandatory per user, anti-cheat) |
| 6 | `resources/js/pages/ujian/Hasil.svelte` (141) | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Hasil ujian mode-ujian (dirender `Siswa\UjianController@hasil` saat mode ujian) |
| — | 16 baris route `/ujian/*` di `routes/web.php` | `95c4a84` Hapus halaman /ujian | **CANDIDATE** | Route login/logout/daftar + grup `UjianAuthMiddleware` (konfirmasi, mulai, kerjakan, waktu-server, jawaban, submit, lapor, hasil) — restore ke `Modules/CBT/routes/web.php` |

### (b) Admin ujian (dihapus di `ee2b92a`, SOT = `a049293`)

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 7 | `app/Http/Controllers/Admin/UjianController.php` (498) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | CRUD + terbit + token per-ujian/sesi + hasil + koreksi esai (`nilaiEsai`) lintas guru |
| 8 | `resources/js/pages/admin/Ujian/Index.svelte` (643) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Monitoring ujian lintas guru (fitur headline v2) |
| 9 | `resources/js/pages/admin/Ujian/Hasil.svelte` (122) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Rekap hasil per ujian |
| 10 | `resources/js/pages/admin/Ujian/Koreksi.svelte` (182) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Koreksi jawaban esai/manual |
| 11 | `resources/js/pages/admin/Ujian/Soal.svelte` (91) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Lihat soal per ujian (read-only) |
| 12 | `app/Http/Controllers/Admin/PeriodeUjianController.php` (98) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Window periode ujian; model `PeriodeUjian` masih ada |
| 13 | `resources/js/pages/admin/PeriodeUjian/Index.svelte` (336) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | UI periode; keputusan user: perlu/tidak di desain modul baru (event sudah mencakup window) |
| 14 | `app/Http/Controllers/Admin/RuangController.php` (62) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Master ruang; selaras desain final (Kode, Nama, Kapasitas, Lokasi) |
| 15 | `resources/js/pages/admin/Ruang/Index.svelte` (36) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Tipis; restore murah |
| 16 | `app/Http/Controllers/Admin/BankSoalController.php` (278) | `ee2b92a` Hapus fitur Ujian CBT di admin | **SKIP** | User menetapkan Bank Soal = sumber tunggal di sisi guru; akses admin lintas-matpel bertentangan dengan arah itu |
| 17 | `resources/js/pages/admin/BankSoal/Index.svelte` (515) | `ee2b92a` Hapus fitur Ujian CBT di admin | **SKIP** | Alasan sama dengan #16 |
| — | 42 baris route admin di `routes/admin.php` (periode, ruang, ujian, bank-soal) | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** (kecuali bank-soal) | Restore selektif ke `Modules/CBT/routes/admin.php` |
| — | 25 baris menu di `resources/js/layouts/admin/AdminLayout.svelte` | `ee2b92a` Hapus fitur Ujian CBT di admin | **CANDIDATE** | Menu admin ujian (dipindah ke layout modul) |

### (c) Panitia — jadwal gen-2 `jadwal_ujians` + Event/Group (dihapus di `6bbf814`, SOT = `ee2b92a`)

> Generasi "bersih" hasil rebuild (`0a83440` Fitur jadwal ujian bersih) — tetap
> ditulis ulang karena user menghendaki desain jadwal resmi yang baru di
> `Modules/CBT` (ikut struktur Exo: event name-only, group hierarkis via
> `parent_id`, jadwal link ke event + bank soal).

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 18 | `app/Http/Controllers/Panitia/JadwalUjianController.php` (200) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Sync ke model `Ujian` lama per-kelas; desain baru = 1 jadwal gabungan per mapel/hari. Ambil ide UI/validasi saja |
| 19 | `app/Models/JadwalUjian.php` (56) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Diganti model jadwal baru di modul |
| 20 | `app/Models/JadwalUjianSesi.php` (41) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Diganti model sesi baru di modul |
| 21 | `app/Services/JadwalUjianSyncService.php` (109) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Logika sync ke `Ujian` lama; konsep auto-assign kapasitas berguna sebagai referensi |
| 22 | `database/migrations/2026_10_16_160000_create_jadwal_ujians_table.php` (58) | `6bbf814` Bersihkan semua fitur ujian non-guru | **REF** | Jangan restore ke `database/migrations`; skema jadi acuan migrasi modul |
| 23 | `resources/js/pages/panitia/JadwalUjian/Index.svelte` (68) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | UI diganti desain baru |
| 24 | `resources/js/pages/panitia/JadwalUjian/Form.svelte` (155) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Form diganti desain baru |
| 25 | `resources/js/pages/panitia/JadwalUjian/Detail.svelte` (81) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Detail diganti desain baru |
| 26 | `app/Http/Controllers/Panitia/EventController.php` (61) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Event akan didesain ulang name-only di modul |
| 27 | `app/Http/Controllers/Panitia/GroupController.php` (103) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Group akan didesain ulang hierarkis (`parent_id`) di modul |
| 28 | `app/Models/Event.php` (22) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Diganti model event baru di modul |
| 29 | `resources/js/pages/panitia/Event/Index.svelte` (182) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | UI diganti desain baru |
| 30 | `resources/js/pages/panitia/Group/Index.svelte` (187) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | UI diganti desain baru |
| 31 | `app/Http/Controllers/Panitia/UjianController.php` (78, gen-2) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Versi ramping alur `Ujian`-model lama (token sesi, kartu, berita acara, monitoring); fitur monitoring & berita acara diambil idenya untuk desain jadwal-baru |
| 32 | `resources/js/pages/panitia/Ujian/Index.svelte` (116) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Alur `Ujian`-model lama |
| 33 | `resources/js/pages/panitia/Ujian/Create.svelte` (80) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Alur `Ujian`-model lama |
| 34 | `resources/js/pages/panitia/Ujian/Show.svelte` (435) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Alur `Ujian`-model lama |
| 35 | `resources/js/pages/panitia/Ujian/Monitoring.svelte` (137) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Real-time proctor monitoring = fitur headline v2; **tulis ulang** menempel ke jadwal/sesi baru, jangan restore verbatim |
| 36 | `resources/js/pages/panitia/Ujian/BeritaAcara.svelte` (108) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** (REF) | Berita acara + PDF per sesi (E2E 304/304) — tulis ulang menempel ke jadwal baru |
| — | Route panitia di `routes/web.php`: group/event/jadwal-ujian (16 baris) | `6bbf814` Bersihkan semua fitur ujian non-guru | **SKIP** | Diganti route modul baru |
| — | Route panitia-ujian CBT v2 (34 baris, `panitia-only`) | `67c679b` Bersihkan sisa routes panitia | **SKIP** (REF) | Daftar lengkap endpoint monitoring/kartu/berita-acara/token — berguna sebagai **checklist API** saat menulis ulang |

### (d) Kartu ujian (dihapus di `6bbf814` + `6fd941c` + `80b01b6`, SOT controller = `ee2b92a`)

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 37 | `app/Http/Controllers/Panitia/KartuController.php` (99) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | Menu 'Kartu Ujian' = permintaan eksplisit user (Generate Semua/per-kelas, statistik). Adaptasi: filter `KATEGORI_BESAR` + `guruKelas` masih valid di model HEAD |
| 38 | `resources/js/pages/panitia/Kartu/Index.svelte` (99) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | UI kartu per kelas + status password_ujian |
| — | 1 baris route `siswa.ujian.kartu` | `6fd941c` Hapus kartu ujian siswa | **CANDIDATE** | Unduh kartu PDF oleh siswa |
| — | 1 baris route `guru.ujian.sesi.kartu` | `80b01b6` Hapus kartu peserta guru | **CANDIDATE** | Kartu peserta per sesi (guru) — method `kartuPeserta` masih ada di `GuruUjianAkademikController` |

### (e) View siswa ujian — dashboard siswa (dihapus di `6bbf814`, SOT = `ee2b92a`)

> Controller `Siswa\UjianController` + 8 route `siswa.ujian.*` (kecuali kartu) **masih
> hidup di HEAD** — restore 4 view ini = alur dashboard-siswa pulih penuh.

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 39 | `resources/js/pages/siswa/Ujian/Index.svelte` (291) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | Daftar ujian siswa (hanya kuis/UH informal; UTS/UAS/USBK via `/ujian/login`) |
| 40 | `resources/js/pages/siswa/Ujian/Konfirmasi.svelte` (171) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | Konfirmasi + tata tertib (mandatory anti-cheat) |
| 41 | `resources/js/pages/siswa/Ujian/Kerjakan.svelte` (1153) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | **Exam player** — model simpan localStorage-first + autosave debounce 1s, navigasi fetch() (fix lag), shuffle deterministik per-attempt, layout contract desktop/mobile, A-/A+ 4 level. Live-test via Elna Corwin; grading E2E 16/17 via API |
| 42 | `resources/js/pages/siswa/Ujian/Hasil.svelte` (219) | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | Hasil per siswa |
| — | 24 baris di `resources/js/layouts/app/UserLayout.svelte` | `6bbf814` Bersihkan semua fitur ujian non-guru | **CANDIDATE** | Menu panitia/ujian (dipindah ke layout modul) |

### (f) Panitia — jadwal gen-1 `cbt_jadwals` (dihapus di `4fb281e`, SOT = `46be2ca`)

> Generasi pertama yang sempat dinilai bermasalah lalu dihapus & dibangun ulang
> ("bersih") — jangan restore verbatim.

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 43 | `app/Http/Controllers/Panitia/UjianController.php` (999, gen-1) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** | Generasi bermasalah; diganti desain baru |
| 44 | `app/Http/Controllers/Panitia/RuangController.php` (55) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **CANDIDATE** | Room master panitia (Kode, Nama, Kapasitas, Lokasi) — selaras desain final; tidak terikat model jadwal lama |
| 45 | `resources/js/pages/panitia/Ruang/Index.svelte` (120) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **CANDIDATE** | UI room master |
| 46 | `app/Models/Jadwal.php` (55) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** (REF) | Diganti model jadwal baru di modul |
| 47 | `app/Models/JadwalSesi.php` (37) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** (REF) | Diganti model sesi baru di modul |
| 48 | `app/Services/JadwalPublishService.php` (128) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** (REF) | Logika terbit; tulis ulang untuk desain baru |
| 49 | `app/Services/SesiAssignService.php` (161) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** (REF) | Auto-assign sesi by capacity + overflow — **algoritma ini DIPAKAI** di desain baru (sesi otomatis per jadwal); porting logika, bukan file |
| 50 | `resources/js/pages/panitia/Ujian/TambahJadwal.svelte` (223) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** (REF) | Alur form jadi referensi UX |
| 51 | `resources/js/pages/panitia/Ujian/EditJadwal.svelte` (150) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** | Diganti desain baru |
| 52 | `resources/js/pages/panitia/Ujian/Jadwal.svelte` (109) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** | Diganti desain baru |
| 53 | `resources/js/pages/panitia/Ujian/DetailJadwal.svelte` (103) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** | Diganti desain baru |
| — | 13 baris route + 6 baris UserLayout | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **SKIP** | Diganti route modul baru |

### (g) Migration & tabel DB (REF — jangan restore verbatim ke `database/migrations`)

| # | Path | Dihapus di commit | Status | Alasan |
|---|------|-------------------|--------|--------|
| 54 | `database/migrations/2026_08_20_100001_create_periode_ujians_table.php` (33) | `ee2b92a` Hapus fitur Ujian CBT di admin | **REF** | Tabel `periode_ujians` kemungkinan sudah ada di DB produksi (migrasi sempat jalan sebelum dihapus). Modul baru: putuskan pakai ulang tabel vs migrasi modul baru |
| 55 | `database/migrations/2026_10_16_160000_create_jadwal_ujians_table.php` (58) | `6bbf814` Bersihkan semua fitur ujian non-guru | **REF** | Acuan skema; nama tabel final diputuskan di desain modul |
| 56 | `database/migrations/2026_10_16_130000_create_cbt_jadwals_table.php` (46) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **REF** | Acuan skema gen-1 |
| 57 | `database/migrations/2026_10_16_140000_add_mode_to_cbt_jadwals_table.php` (22) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **REF** | Kolom `mode` luring/daring — konsep dipakai di desain baru |
| 58 | `database/migrations/2026_10_16_150000_create_cbt_jadwal_kelas_table.php` (24) | `4fb281e` Hapus fitur ujian resmi (jadwal baru) | **REF** | Pivot jadwal→kelas — konsep dipakai di desain baru |

> Catatan: tabel inti alur ujian (`ujians`, `ujian_pengerjaans`, `sesi_ujians`,
> `sesi_ujian_pesertas`, `jawaban_siswas`, `ruang_ujians`, `periode_ujians`)
> **tidak pernah dihapus** — model & migrasinya masih di HEAD.

---

## 3. Ringkasan penilaian

| Kelompok | File | CANDIDATE | SKIP / REF |
|----------|------|-----------|------------|
| (a) Login & sesi ujian siswa `/ujian/*` | 6 + route | 6 + route | 0 |
| (b) Admin ujian | 11 + route/layout | 9 + route/layout | 2 (BankSoal admin) |
| (c) Panitia jadwal gen-2 + Event/Group + alur Ujian-model lama | 19 + route | 0 | 19 (REF) |
| (d) Kartu ujian | 2 + 2 route | 2 + 2 route | 0 |
| (e) View siswa ujian (dashboard) | 4 + layout | 4 + layout | 0 |
| (f) Panitia jadwal gen-1 | 11 | 2 (Ruang master) | 9 (REF) |
| (g) Migrasi | 5 | 0 | 5 (REF) |
| **Total** | **58 item (56 file + 2 grup edit)** | **23 file + route/layout** | **33 file + route** |

---

## 4. Commit SHA "sumber kebenaran" (SOT) per kelompok

File diambil dari commit TERAKHIR sebelum penghapusan, saat file masih utuh:

| Kelompok | SOT (SHA + pesan) | Perintah pola |
|----------|-------------------|---------------|
| (a) `/ujian/*` login & sesi siswa | `d0d025f` Tambah sync terbitkan jadwal (= `95c4a84~1`) | `git show d0d025f:<path> > ...` |
| (b) Admin ujian | `a049293` Perbaiki menu panitia untuk admin (= `ee2b92a~1`) | `git show a049293:<path> > ...` |
| (c) Panitia gen-2 + Event/Group (referensi) | `ee2b92a` Hapus fitur Ujian CBT di admin (= `6bbf814~1`) | `git show ee2b92a:<path> > ...` |
| (d) Kartu ujian | `ee2b92a` (= `6bbf814~1`) untuk controller+view; route kartu dari `67c679b~1` / `80b01b6~1` | `git show ee2b92a:<path> > ...` |
| (e) View siswa ujian | `ee2b92a` (= `6bbf814~1`) | `git show ee2b92a:<path> > ...` |
| (f) Panitia jadwal gen-1 (referensi) | `46be2ca` Sync jadwal ke ujian saat diterbitkan (= `4fb281e~1`) | `git show 46be2ca:<path> > ...` |
| (g) Migrasi (referensi skema) | SOT masing-masing kelompok di atas | `git show <SOT>:database/migrations/<file>` |

---

## 5. Saran penempatan di `Modules/CBT/...`

Asumsi modul bernama `CBT` (namespace `Modules\CBT`). Struktur mengikuti konvensi
nwidart/laravel-modules. **Keputusan arsitektur #3 di bawah menentukan apakah
frontend ikut pindah ke modul atau tetap di root.**

| Kelompok sumber | Target di `Modules/CBT/` | Catatan adaptasi |
|-----------------|--------------------------|------------------|
| `app/Http/Controllers/UjianLoginController.php`, `UjianModeController.php` | `Modules/CBT/app/Http/Controllers/UjianLoginController.php`, `.../UjianModeController.php` | Namespace → `Modules\CBT\Http\Controllers`; import model tetap `App\Models\*` (model inti dipertahankan di root — lihat keputusan #3) |
| `app/Http/Controllers/Admin/{Ujian,PeriodeUjian,Ruang}Controller.php` | `Modules/CBT/app/Http/Controllers/Admin/{Ujian,PeriodeUjian,Ruang}Controller.php` | Namespace → `Modules\CBT\Http\Controllers\Admin` |
| `app/Http/Controllers/Panitia/{KartuController,RuangController}.php` | `Modules/CBT/app/Http/Controllers/Panitia/{KartuController,RuangController}.php` | `KartuController`: verifikasi ulang query `KATEGORI_BESAR` + relasi `guruKelas` terhadap model HEAD |
| `resources/js/pages/ujian/*.svelte` | `Modules/CBT/resources/js/pages/ujian/*.svelte` **atau** tetap `resources/js/pages/ujian/` di root | Tergantung keputusan #3 (strategi aset frontend modul). Nama komponen Inertia (`ujian/Login` dll.) harus sinkron dengan yang dirender controller |
| `resources/js/pages/siswa/Ujian/*.svelte` | `Modules/CBT/resources/js/pages/siswa/Ujian/*.svelte` **atau** root | WAJIB: nama komponen persis `siswa/Ujian/Index|Konfirmasi|K erjakan|Hasil` karena `Siswa\UjianController` (tetap di root) merender nama itu |
| `resources/js/pages/admin/Ujian/*.svelte`, `admin/PeriodeUjian/Index.svelte`, `admin/Ruang/Index.svelte` | `Modules/CBT/resources/js/pages/admin/...` **atau** root | Sinkron dengan nama render di controller admin modul |
| `resources/js/pages/panitia/Kartu/Index.svelte`, `panitia/Ruang/Index.svelte` | `Modules/CBT/resources/js/pages/panitia/...` **atau** root | Idem |
| Route `/ujian/*` (16 baris, ex-`routes/web.php`) | `Modules/CBT/routes/web.php` | Daftarkan via `RouteServiceProvider` modul; pertahankan nama route (`ujian.login`, `ujian.daftar`, `ujian.konfirmasi`, …) karena view & redirect memakainya |
| Route admin (periode, ruang, ujian — ex-`routes/admin.php`) | `Modules/CBT/routes/admin.php` (atau digabung ke `web.php` modul dengan prefix `admin`) | Pertahankan nama route (`periode-ujian.*`, `ruang.*`, `ujian.*`) |
| Route kartu siswa/guru (2 baris) | Kembalikan ke `routes/web.php` root ATAU pindah ke modul | Method tujuan (`kartuSaya`, `kartuPeserta`) masih ada di controller root |
| Menu AdminLayout/UserLayout (49 baris total) | Layout modul / komponen menu modul | Jangan edit layout root di luar kebutuhan |
| File SKIP/REF (jadwal gen-1 & gen-2, Event/Group, Monitoring, BeritaAcara, SesiAssignService, migrasi) | **Tidak dipindah** — tetap di git history sebagai referensi | Eksekutor baca via `git show <SOT>:<path>` saat menulis ulang |

Dependensi bersama yang tetap di root (jangan diduplikasi ke modul):
`App\Models\*` (Ujian, UjianPengerjaan, SesiUjian, SesiUjianPeserta, JawabanSiswa,
RuangUjian, PeriodeUjian, Siswa, Kelas…), `App\Support\Toast`,
`app/Http/Middleware/UjianAuthMiddleware.php` (guard `/ujian`), Wayfinder actions
di `resources/js/actions/` (regenerate setelah route modul terdaftar).

---

## 6. Urutan langkah restorasi (siap dijalankan Agen Eksekutor)

> Prasyarat: gate plugin Composer sudah unblock, `nwidart/laravel-modules`
> terinstall, modul `CBT` ter-generate (`php artisan module:make CBT`).
> Semua perintah di bawah READ terhadap history; yang ditulis hanya file baru di
> `Modules/CBT/`. JANGAN checkout/restore ke lokasi lama.

**Langkah 0 — Verifikasi & persiapan**
1. `git status` bersih, di branch `fix-cbt-v2`; `git log --oneline -3` cocok dengan
   audit ini (HEAD = `dc16b65`).
2. Generate modul: `php artisan module:make CBT`; pastikan `Modules/CBT/`
   terdaftar di `composer.json` autoload / `modules_statuses.json`.

**Langkah 1 — Restore kelompok (a): login & sesi ujian siswa (SOT `d0d025f`)**
3. Untuk tiap file CANDIDATE kelompok (a):
   `git show d0d025f:app/Http/Controllers/UjianLoginController.php > Modules/CBT/app/Http/Controllers/UjianLoginController.php` (dst. untuk `UjianModeController.php` dan 4 file `resources/js/pages/ujian/*.svelte`).
4. Ubah namespace controller → `Modules\CBT\Http\Controllers`; sesuaikan
   `use` model ke `App\Models\*`; pastikan nama komponen Inertia tetap
   `ujian/Login|Daftar|Konfirmasi|Hasil`.
5. Salin 16 baris route `/ujian/*` dari `git show d0d025f:routes/web.php`
   (baris 38–53) ke `Modules/CBT/routes/web.php`; pertahankan nama route &
   grup `UjianAuthMiddleware`.

**Langkah 2 — Restore kelompok (e): view siswa ujian (SOT `ee2b92a`)**
6. `git show ee2b92a:resources/js/pages/siswa/Ujian/<Index,Konfirmasi,Kerjakan,Hasil>.svelte`
   → lokasi frontend yang diputuskan (modul atau root). Nama komponen HARUS
   tetap `siswa/Ujian/*` (dirender `Siswa\UjianController` di root).
7. Smoke test: route `siswa.ujian.index|konfirmasi|kerjakan|hasil` tidak lagi
   error komponen hilang.

**Langkah 3 — Restore kelompok (b): admin ujian (SOT `a049293`)**
8. `git show a049293:app/Http/Controllers/Admin/<Ujian,PeriodeUjian,Ruang>Controller.php`
   → `Modules/CBT/app/Http/Controllers/Admin/`; namespace → `Modules\CBT\…`.
9. `git show a049293:resources/js/pages/admin/<Ujian/{Index,Hasil,Koreksi,Soal},PeriodeUjian/Index,Ruang/Index>.svelte`
   → lokasi frontend modul/root.
10. Salin blok route admin (periode, ruang, ujian — TANPA bank-soal) dari
    `git show a049293:routes/admin.php` ke route modul; pertahankan nama route.
11. Tambahkan menu admin ujian (25 baris ex-AdminLayout) ke layout/menu modul.

**Langkah 4 — Restore kelompok (d): kartu ujian (SOT `ee2b92a`)**
12. `git show ee2b92a:app/Http/Controllers/Panitia/KartuController.php` →
    `Modules/CBT/app/Http/Controllers/Panitia/`; verifikasi query
    `KATEGORI_BESAR` + `guruKelas` terhadap model HEAD.
13. `git show ee2b92a:resources/js/pages/panitia/Kartu/Index.svelte` → frontend.
14. Kembalikan 2 route kartu (`siswa.ujian.kartu` dari `67c679b~1:routes/web.php`,
    `guru.ujian.sesi.kartu` dari `80b01b6~1:routes/web.php`) — ke modul atau root
    sesuai keputusan.

**Langkah 5 — Restore kelompok (f) parsial: room master panitia (SOT `46be2ca`)**
15. `git show 46be2ca:app/Http/Controllers/Panitia/RuangController.php` →
    `Modules/CBT/app/Http/Controllers/Panitia/`; `git show 46be2ca:resources/js/pages/panitia/Ruang/Index.svelte` → frontend.

**Langkah 6 — Integrasi & verifikasi**
16. Regenerate Wayfinder: `php artisan wayfinder:generate` (route modul baru).
17. `bun run build` (atau `composer run dev`); `bun run types:check`;
    `vendor/bin/pint --dirty` bila ada file PHP tersentuh.
18. Jalankan suite terkait: `php artisan test --compact` (setidaknya test ujian/
    grading), `bun run test:frontend`.
19. Verifikasi manual kritis: alur `/ujian/login` → daftar → konfirmasi →
    kerjakan → submit → hasil (catatan: kasus 404 `/ujian/konfirmasi/{id}`
    dari 2026-10-15 wajib di-re-test; jangan klaim lolos tanpa bukti).
20. Menu panitia (UserLayout, 24 baris ex-`6bbf814`) dipindah ke menu modul.

**Langkah 7 — Yang TIDAK dilakukan di fase restore**
- Jangan restore file SKIP/REF (jadwal gen-1/gen-2, Event/Group, Monitoring,
  BeritaAcara, BankSoal admin, migrasi lama) — itu dikerjakan di fase tulis-ulang
  desain jadwal resmi (butuh keputusan #1).
- Jangan menambah migrasi root dari file lama; tabel inti sudah ada di DB.

---

## 7. Hal yang butuh keputusan user / parent

### Keputusan 1 — Sistem jadwal resmi: restore vs tulis ulang (DAMPAK TERBESAR)
Dua generasi kode jadwal resmi ada di history (gen-1 `cbt_jadwals` di `4fb281e`,
gen-2 `jadwal_ujians` "bersih" di `6bbf814`), keduanya SKIP dari restore verbatim.
Pilihan:
- **(A, direkomendasikan)** Tulis ulang bersih di `Modules/CBT` mengikuti desain
  resmi yang sudah disepakati user (event name-only, group hierarkis `parent_id`,
  1 jadwal gabungan per mapel/hari, bank soal pooled & shuffle, sesi otomatis
  by kapasitas + overflow, jeda antar sesi, token per sesi, 2–4 jadwal/hari,
  luring vs daring) — memakai kode lama hanya sebagai referensi
  (`SesiAssignService` untuk algoritma auto-assign, `Monitoring`/`BeritaAcara`
  untuk pola UI proctor).
- (B) Restore gen-2 `jadwal_ujians` verbatim — cepat tapi menghidupkan kembali
  arsitektur sync-ke-`Ujian`-per-kelas yang justru mau ditinggalkan.

### Keputusan 2 — BankSoal di sisi admin: tetap SKIP?
Dokumen ini menandai `Admin\BankSoalController` + view-nya SKIP karena user
menetapkan bank soal = sumber tunggal di sisi guru. Konfirmasi: apakah admin
benar-benar tidak butuh tampilan bank soal lintas matpel (read-only pun tidak)?

### Keputusan 3 — Batas modul: apa yang pindah ke `Modules/CBT`, apa yang tetap di root?
- Model inti (`Ujian`, `SesiUjian`, `UjianPengerjaan`, `JawabanSiswa`,
  `RuangUjian`, `PeriodeUjian`, …) — rekomendasi: **tetap di root** sebagai
  "core" bersama; controller modul mengimpor `App\Models\*`. Alternatif: pindah
  semua ke modul (refactor besar, risiko tinggi).
- `Siswa\UjianController` (masih hidup di root, merender view ujian) — pindah ke
  modul atau tetap? Kalau tetap, view `siswa/Ujian/*` harus bisa di-resolve
  dari root.
- Frontend Svelte: konvensi nwidart menaruh aset di `Modules/CBT/resources/js`,
  tapi build Vite root saat ini mengharapkan `resources/js`. Putuskan satu
  strategi sebelum eksekutor memindah 21 file view (salah pilih = build pecah).
- `UjianAuthMiddleware` — pindah ke modul atau tetap di root (dipakai route modul).

### Keputusan 4 (kecil) — Periode ujian: dipertahankan atau dilebur ke Event?
`PeriodeUjian` (window tanggal) vs konsep `Event` di desain baru — apakah
keduanya perlu, atau periode dilebur jadi atribut event? (Mempengaruhi apakah
item #12–13 benar-benar direstore.)
