# Peta Migrasi Modul LMS (nwidart/laravel-modules) — Agen 2

> **Status dokumen:** READ-ONLY audit. Tidak ada kode yang dipindahkan, tidak ada commit/push,
> tidak ada akses VPS. Ini peta presisi untuk Agen Eksekutor setelah gate plugin Composer unblock.
> **Branch saat audit:** `fix-cbt-v2` (repo kotor karena tim paralel in-flight — TIDAK disentuh).
> **Tanggal audit:** 2026-10-16.

---

## 1. Inventaris lengkap (scope LMS = Materi + Tugas)

### 1.1 Controller (4)

| # | Path lama | Namespace lama | Method publik |
|---|-----------|----------------|---------------|
| 1 | `app/Http/Controllers/Guru/MateriController.php` | `App\Http\Controllers\Guru` | `index`, `katalog`, `salin`, `edit`, `update`, `store`, `destroy`, `unduh` (8) |
| 2 | `app/Http/Controllers/Guru/TugasController.php` | `App\Http\Controllers\Guru` | `index`, `edit`, `store`, `update`, `destroy`, `pengumpulan`, `nilai`, `pengumpulanUnduh`, `unduh` (9) |
| 3 | `app/Http/Controllers/Siswa/MateriController.php` | `App\Http\Controllers\Siswa` | `index`, `show`, `lihat`, `unduh` (4) |
| 4 | `app/Http/Controllers/Siswa/TugasController.php` | `App\Http\Controllers\Siswa` | `index`, `show`, `kumpul`, `unduh` (4) |

Catatan penting:
- Keempatnya `extends App\Http\Controllers\BaseAppController` dan memakai
  `Illuminate\Routing\Controllers\HasMiddleware` dengan closure role-check inline
  (`guru` / `siswa`) — middleware dideklarasikan per-controller, jadi pindah file
  otomatis memindahkan perlindungan role. Tidak ada middleware route-level khusus
  materi/tugas di `routes/web.php`.
- Semua redirect pasca-aksi memakai `Redirect::back()` (bukan `route(...)`) →
  **tidak ada nama route PHP-side yang harus disinkronkan**. Grep route name di
  `app/` menghasilkan 0 hasil di luar `routes/web.php`.

### 1.2 Route (25) — semua di `routes/web.php`, grup `['auth','app-only']`, prefix `app`, name prefix `app.`

Middleware grup: `auth` + `app-only` (`AppOnlyMiddleware`, didaftar di
`bootstrap/app.php:44`). URL live hari ini: `/app/guru/materi` dst.

| # | Method | URI (setelah prefix `app`) | Nama | Controller@method lama |
|---|--------|----------------------------|------|------------------------|
| 1 | GET | `guru/materi` | `app.guru.materi.index` | Guru\MateriController@index |
| 2 | GET | `guru/materi/katalog` | `app.guru.materi.katalog` | Guru\MateriController@katalog |
| 3 | POST | `guru/materi` | `app.guru.materi.store` | Guru\MateriController@store |
| 4 | POST | `guru/materi/salin` | `app.guru.materi.salin` | Guru\MateriController@salin |
| 5 | GET | `guru/materi/{materi}/edit` | `app.guru.materi.edit` | Guru\MateriController@edit |
| 6 | PUT | `guru/materi/{materi}` | `app.guru.materi.update` | Guru\MateriController@update |
| 7 | DELETE | `guru/materi/{materi}` | `app.guru.materi.destroy` | Guru\MateriController@destroy |
| 8 | GET | `guru/materi/{materi}/unduh` | `app.guru.materi.unduh` | Guru\MateriController@unduh |
| 9 | GET | `guru/tugas` | `app.guru.tugas.index` | Guru\TugasController@index |
| 10 | POST | `guru/tugas` | `app.guru.tugas.store` | Guru\TugasController@store |
| 11 | GET | `guru/tugas/{tugas}/edit` | `app.guru.tugas.edit` | Guru\TugasController@edit |
| 12 | PUT | `guru/tugas/{tugas}` | `app.guru.tugas.update` | Guru\TugasController@update |
| 13 | DELETE | `guru/tugas/{tugas}` | `app.guru.tugas.destroy` | Guru\TugasController@destroy |
| 14 | GET | `guru/tugas/{tugas}/unduh` | `app.guru.tugas.unduh` | Guru\TugasController@unduh |
| 15 | GET | `guru/tugas/{tugas}/pengumpulan` | `app.guru.tugas.pengumpulan` | Guru\TugasController@pengumpulan |
| 16 | PUT | `guru/tugas/{tugas}/nilai` | `app.guru.tugas.nilai` | Guru\TugasController@nilai |
| 17 | GET | `guru/tugas/{tugas}/pengumpulan/{pengumpulan}/unduh` | `app.guru.tugas.pengumpulan.unduh` | Guru\TugasController@pengumpulanUnduh |
| 18 | GET | `materi` | `app.siswa.materi.index` | Siswa\MateriController@index |
| 19 | GET | `materi/{materi}` | `app.siswa.materi.show` | Siswa\MateriController@show |
| 20 | GET | `materi/{materi}/lihat` | `app.siswa.materi.lihat` | Siswa\MateriController@lihat |
| 21 | GET | `materi/{materi}/unduh` | `app.siswa.materi.unduh` | Siswa\MateriController@unduh |
| 22 | GET | `tugas` | `app.siswa.tugas.index` | Siswa\TugasController@index |
| 23 | GET | `tugas/{tugas}` | `app.siswa.tugas.show` | Siswa\TugasController@show |
| 24 | POST | `tugas/{tugas}/kumpul` | `app.siswa.tugas.kumpul` | Siswa\TugasController@kumpul |
| 25 | GET | `tugas/{tugas}/unduh` | `app.siswa.tugas.unduh` | Siswa\TugasController@unduh |

`routes/admin.php` = 0 route materi/tugas (grep bersih). **Keputusan audit: nama route
& URI TIDAK diubah** — migrasi murni namespace, URL tetap `/app/...` sehingga tidak
ada link eksternal/bookmark yang putus.

### 1.3 View Svelte (6 halaman) + string Inertia

| Path view | String render di controller |
|-----------|-----------------------------|
| `resources/js/pages/guru/Materi/Index.svelte` | `guru/Materi/Index` |
| `resources/js/pages/guru/Tugas/Index.svelte` | `guru/Tugas/Index` |
| `resources/js/pages/guru/Tugas/Pengumpulan.svelte` | `guru/Tugas/Pengumpulan` |
| `resources/js/pages/siswa/Materi/Index.svelte` | `siswa/Materi/Index` |
| `resources/js/pages/siswa/Materi/Detail.svelte` | `siswa/Materi/Detail` |
| `resources/js/pages/siswa/Tugas/Index.svelte` | `siswa/Tugas/Index` |
| `resources/js/pages/siswa/Tugas/Detail.svelte` | `siswa/Tugas/Detail` |

(7 file, 6 halaman unik — guru/Materi/Index dirender 3x.)

Komponen pembantu yang dipakai halaman LMS (tetap di core, dipakai bersama):
- `resources/js/components/tugas/TugasFormModal.svelte` (diimpor guru/Tugas/Index)
- `resources/js/components/penilaian/*` — PenilaianCreateModal/EditModal tidak dipakai
  halaman LMS (hanya match grep nama file karena direktori `penilaian`), tidak ada aksi.

### 1.4 Model (3) + relasi

| Model | Tabel | Relasi |
|-------|-------|--------|
| `app/Models/Materi.php` | `materis` (default) | `belongsTo(Guru)`, `belongsTo(GuruKelas)` |
| `app/Models/Tugas.php` | `tugases` | `belongsTo(Guru)`, `belongsTo(GuruKelas)`, `belongsTo(Penilaian)`, `hasMany(TugasPengumpulan)` |
| `app/Models/TugasPengumpulan.php` | `tugas_pengumpulans` | `belongsTo(Tugas)`, `belongsTo(Siswa, 'siswa_nisn', 'nisn')` |

Model lain yang **dirujuk** controller LMS tapi tetap di core: `Guru`, `GuruKelas`,
`Siswa`, `SiswaKelas`, `Penilaian`, `DetailPenilaian`, `TahunAjaran`. Support class:
`App\Support\HtmlSanitizer`, `App\Support\Toast` (tetap di core — di-refer tanpa pindah).

---

## 2. Cross-reference — file yang me-link ke route materi/tugas (PENTING)

### 2.1 Navigasi & dashboard (frontend)

| File | Kaitan |
|------|--------|
| `resources/js/layouts/app/UserLayout.svelte` | Sidebar nav: `GuruMateriController.index().url` (2x), `GuruTugasController.index().url` (2x), `SiswaMateriController.index().url` (2x), `SiswaTugasController.index().url` (2x). Import dari `@/actions/App/Http/Controllers/...` |
| `resources/js/pages/Dashboard.svelte` | Kartu ringkasan: `MateriController.index().url`, `TugasController.index().url`, `GuruMateriController.index().url`, `GuruTugasController.index().url` — plus tipe `MateriBaru`/`TugasBelum` dari props dashboard |
| `resources/js/pages/guru/Matpel.svelte` | `guruMateriUrl` / `siswaMateriUrl` via Wayfinder actions |

Semua memakai **Wayfinder-generated URL helpers** (bukan string nama route) →
selama URI tidak berubah (`/app/...` tetap), helper tetap valid setelah regenerasi.

### 2.2 File Wayfinder ter-generate (HARUS diregenerasi pasca-migrasi)

| Grup | File |
|------|------|
| actions | `resources/js/actions/App/Http/Controllers/Guru/MateriController.ts`, `.../Guru/TugasController.ts`, `.../Siswa/MateriController.ts`, `.../Siswa/TugasController.ts` |
| routes | `resources/js/routes/app/guru/materi/index.ts`, `resources/js/routes/app/guru/tugas/index.ts`, `resources/js/routes/app/guru/tugas/pengumpulan/index.ts`, `resources/js/routes/app/siswa/materi/index.ts`, `resources/js/routes/app/siswa/tugas/index.ts` |

File-file ini **embed docblock `@see \App\Http\Controllers\Guru\MateriController` +
path file + URI**. Setelah controller pindah namespace, isi lama jadi basi. Regenerasi
via `php artisan wayfinder:generate` (atau build Vite — plugin
`@laravel/vite-plugin-wayfinder` di `vite.config.ts`). Import path baru di TS menjadi
`@/actions/Modules/LMS/...` → **semua import di §2.1 ikut berubah path-nya**
(nav, dashboard, matpel, halaman materi/tugas sendiri). Ini daftar edit wajib
frontend — lihat §6.

### 2.3 Konsumen PHP model LMS di luar controller LMS (alasan model TETAP di core)

| File | Pakai model |
|------|-------------|
| `app/Http/Controllers/DashboardController.php` | `Materi`, `Tugas` (ringkasan dashboard guru & siswa) |
| `app/Http/Controllers/MataPelajaranGuruController.php` | `Materi` (total_materi, last_materi per matpel) |
| `app/Http/Controllers/Siswa/PenilaianController.php` | `Tugas` (nilai tugas masuk rekap penilaian siswa) |
| `app/Services/LaporanExportService.php` | `Materi`, `Tugas`, `TugasPengumpulan` (sheet laporan admin/panitia) |
| `database/factories/MateriFactory.php`, `TugasFactory.php` | refer `App\Models\...` |

**Keputusan audit: model `Materi`, `Tugas`, `TugasPengumpulan` TIDAK dipindah ke
modul.** Tetap `App\Models\...` di core karena 4+ konsumen core memakainya.
Migrasi tabel/file migrasi juga tetap di `database/migrations/` (tidak dipindah).

### 2.4 Kopling Penilaian ↔ Tugas (batas modul)

`Guru\TugasController` membuat/menghapus baris `Penilaian` (`penilaian_id`) dan menulis
`DetailPenilaian::updateOrCreate` saat menilai pengumpulan (baris 131, 184, 215–216,
300–303). Ini kopling LMS→CBT/Core terkuat. Opsi: (a) biarkan controller LMS memakai
`App\Models\Penilaian` langsung (pragmatis, disarankan fase 1), atau (b) ekstraksi
service `Modules\LMS\app\Services\TugasPenilaianSync` + event `TugasDinilai` yang
didengar modul penilaian. **Fase 1: opsi (a)** — pindah namespace saja, jangan refactor
logika.

---

## 3. Tabel migrasi file-by-file

Konvensi nwidart: modul `LMS` → root `Modules/LMS/`, namespace `Modules\LMS\...`.

| Path lama | Path baru | Perubahan namespace |
|-----------|-----------|---------------------|
| `app/Http/Controllers/Guru/MateriController.php` | `Modules/LMS/app/Http/Controllers/Guru/MateriController.php` | `App\Http\Controllers\Guru` → `Modules\LMS\App\Http\Controllers\Guru` |
| `app/Http/Controllers/Guru/TugasController.php` | `Modules/LMS/app/Http/Controllers/Guru/TugasController.php` | sama |
| `app/Http/Controllers/Siswa/MateriController.php` | `Modules/LMS/app/Http/Controllers/Siswa/MateriController.php` | `App\Http\Controllers\Siswa` → `Modules\LMS\App\Http\Controllers\Siswa` |
| `app/Http/Controllers/Siswa/TugasController.php` | `Modules/LMS/app/Http/Controllers/Siswa/TugasController.php` | sama |
| `routes/web.php` (25 baris §1.2 + 4 import alias) | `Modules/LMS/routes/web.php` | import: `use Modules\LMS\App\Http\Controllers\Guru\MateriController as GuruMateriController;` dst. |
| — (baru) | `Modules/LMS/module.json` | scaffold nwidart |
| — (baru) | `Modules/LMS/composer.json` | autoload `Modules\LMS\` → `app/` |
| — (baru) | `Modules/LMS/app/Providers/LMSServiceProvider.php` | `loadRoutesFrom(__DIR__.'/../../routes/web.php')` |
| — (baru, opsional disarankan) | `Modules/LMS/app/Http/Controllers/BaseLMSController.php` | pengganti `App\Http\Controllers\BaseAppController` (lihat §5 risiko #1) |

**Tidak dipindah (tetap di core):** model (3), migrasi, factory, seeder, view Svelte,
komponen, `App\Support\*`, `BaseAppController` (kecuali diputuskan sebaliknya).

---

## 4. Definisi route siap tempel — `Modules/LMS/routes/web.php`

Ditempel persis di dalam grup `auth` + `app-only`, prefix `app`, name `app.` —
atau (lebih bersih) modul mendaftarkan grupnya sendiri di ServiceProvider.
Salin blok berikut:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\LMS\App\Http\Controllers\Guru\MateriController as GuruMateriController;
use Modules\LMS\App\Http\Controllers\Guru\TugasController as GuruTugasController;
use Modules\LMS\App\Http\Controllers\Siswa\MateriController as SiswaMateriController;
use Modules\LMS\App\Http\Controllers\Siswa\TugasController as SiswaTugasController;

Route::middleware(['auth', 'app-only'])->prefix('app')->name('app.')->group(function (): void {
    // ── Guru · Materi ──────────────────────────────────────────────
    Route::get('guru/materi', [GuruMateriController::class, 'index'])->name('guru.materi.index');
    Route::get('guru/materi/katalog', [GuruMateriController::class, 'katalog'])->name('guru.materi.katalog');
    Route::post('guru/materi', [GuruMateriController::class, 'store'])->name('guru.materi.store');
    Route::post('guru/materi/salin', [GuruMateriController::class, 'salin'])->name('guru.materi.salin');
    Route::get('guru/materi/{materi}/edit', [GuruMateriController::class, 'edit'])->name('guru.materi.edit');
    Route::put('guru/materi/{materi}', [GuruMateriController::class, 'update'])->name('guru.materi.update');
    Route::delete('guru/materi/{materi}', [GuruMateriController::class, 'destroy'])->name('guru.materi.destroy');
    Route::get('guru/materi/{materi}/unduh', [GuruMateriController::class, 'unduh'])->name('guru.materi.unduh');

    // ── Guru · Tugas ───────────────────────────────────────────────
    Route::get('guru/tugas', [GuruTugasController::class, 'index'])->name('guru.tugas.index');
    Route::post('guru/tugas', [GuruTugasController::class, 'store'])->name('guru.tugas.store');
    Route::get('guru/tugas/{tugas}/edit', [GuruTugasController::class, 'edit'])->name('guru.tugas.edit');
    Route::put('guru/tugas/{tugas}', [GuruTugasController::class, 'update'])->name('guru.tugas.update');
    Route::delete('guru/tugas/{tugas}', [GuruTugasController::class, 'destroy'])->name('guru.tugas.destroy');
    Route::get('guru/tugas/{tugas}/unduh', [GuruTugasController::class, 'unduh'])->name('guru.tugas.unduh');
    Route::get('guru/tugas/{tugas}/pengumpulan', [GuruTugasController::class, 'pengumpulan'])->name('guru.tugas.pengumpulan');
    Route::put('guru/tugas/{tugas}/nilai', [GuruTugasController::class, 'nilai'])->name('guru.tugas.nilai');
    Route::get('guru/tugas/{tugas}/pengumpulan/{pengumpulan}/unduh', [GuruTugasController::class, 'pengumpulanUnduh'])->name('guru.tugas.pengumpulan.unduh');

    // ── Siswa · Materi ─────────────────────────────────────────────
    Route::get('materi', [SiswaMateriController::class, 'index'])->name('siswa.materi.index');
    Route::get('materi/{materi}', [SiswaMateriController::class, 'show'])->name('siswa.materi.show');
    Route::get('materi/{materi}/lihat', [SiswaMateriController::class, 'lihat'])->name('siswa.materi.lihat');
    Route::get('materi/{materi}/unduh', [SiswaMateriController::class, 'unduh'])->name('siswa.materi.unduh');

    // ── Siswa · Tugas ──────────────────────────────────────────────
    Route::get('tugas', [SiswaTugasController::class, 'index'])->name('siswa.tugas.index');
    Route::get('tugas/{tugas}', [SiswaTugasController::class, 'show'])->name('siswa.tugas.show');
    Route::post('tugas/{tugas}/kumpul', [SiswaTugasController::class, 'kumpul'])->name('siswa.tugas.kumpul');
    Route::get('tugas/{tugas}/unduh', [SiswaTugasController::class, 'unduh'])->name('siswa.tugas.unduh');
});
```

Catatan: 25 route — urutan `guru/materi/katalog` WAJIB sebelum `guru/materi/{materi}/edit`
seperti sekarang (hindari shadowing), sama untuk `tugas`.

---

## 5. Keputusan view: tetap di `resources/js/pages` vs pindah ke modul

**Analisis singkat:**
- Inertia me-resolve halaman dari string (`'guru/Materi/Index'`) terhadap Vite manifest
  tunggal di `resources/js/app.ts`. nwidart menaruh view modul di
  `Modules/LMS/resources/js/...`, yang berarti Vite butuh entry/alias tambahan dan
  Inertia resolver harus tahu dua root — konfigurasi custom, rawan pecah.
- Semua halaman LMS memakai layout & komponen core bersama (`UserLayout.svelte`,
  `TugasFormModal.svelte`). Memindah view memaksa duplikasi/pemindahan komponen juga.
- String render tidak berubah apa pun namespace PHP-nya — view tidak peduli controller
  ada di `App\` atau `Modules\LMS\`.

**Keputusan audit: view TETAP di `resources/js/pages/...` untuk fase 1.**
Implikasi: nol perubahan Vite/Inertia; yang berubah hanya path import Wayfinder
(§2.2/§6). Pemindahan view ke modul bisa jadi fase 2 opsional bila modul LMS
benar-benar dipisah deploy — bukan kebutuhan sekarang.

---

## 6. Daftar cross-reference yang harus ikut diperbarui (checklist eksekutor)

Setelah controller pindah + Wayfinder regenerasi, edit import di file-file ini
(path lama `@/actions/App/Http/Controllers/...` → `@/actions/Modules/LMS/App/Http/Controllers/...`):

1. `resources/js/layouts/app/UserLayout.svelte` — 4 import, 8 pemakaian URL nav
   (baris ~12–19, 72–78, 111–118, 167–189)
2. `resources/js/pages/Dashboard.svelte` — 4 import, 4 pemakaian URL kartu
   (baris ~5–11, 68, 100–108, 147–155)
3. `resources/js/pages/guru/Matpel.svelte` — 2 import, 2 pemakaian
   (baris ~4–5, 42–43)
4. `resources/js/pages/guru/Materi/Index.svelte` — import action controller sendiri
5. `resources/js/pages/guru/Tugas/Index.svelte` — import action controller sendiri
6. `resources/js/pages/guru/Tugas/Pengumpulan.svelte` — import action controller sendiri
7. `resources/js/pages/siswa/Materi/Index.svelte`, `Detail.svelte` — import sendiri
8. `resources/js/pages/siswa/Tugas/Index.svelte`, `Detail.svelte` — import sendiri
9. Hapus file Wayfinder basi di `resources/js/actions/App/Http/Controllers/{Guru,Siswa}/{Materi,Tugas}Controller.ts`
   dan `resources/js/routes/app/{guru,siswa}/{materi,tugas}/...` (biarkan generator
   yang menghapus/menulis ulang; jangan hapus manual bila generator melakukannya).
10. `routes/web.php` — hapus 4 import alias + 25 baris route §1.2 (SETELAH modul
    terbukti load; jangan hapus sebelum verifikasi `php artisan route:list` ganda).

Tidak perlu diubah: `Redirect::back()` (semua aksi tulis), string Inertia render,
model/factory/migrasi, `routes/admin.php`.

---

## 7. Urutan langkah eksekusi (untuk Agen Eksekutor)

1. `composer require nwidart/laravel-modules` — TUNGGU gate plugin Composer unblock.
2. `php artisan module:make LMS` → verifikasi struktur `Modules/LMS/` terbentuk
   (`app/`, `routes/`, `config/`, `module.json`).
3. Buat direktori `Modules/LMS/app/Http/Controllers/{Guru,Siswa}/`.
4. Salin 4 file controller ke path baru (§3); ubah namespace baris 3 tiap file
   (`App\Http\Controllers\Guru` → `Modules\LMS\App\Http\Controllers\Guru`, dst.);
   docblock `@see` Wayfinder akan ikut regenerasi — tidak perlu edit manual.
5. Putuskan `BaseAppController`: (a) buat `Modules\LMS\App\Http\Controllers\BaseLMSController`
   turunan/salinan minimal, ATAU (b) biarkan `use App\Http\Controllers\BaseAppController`
   (disarankan fase 1 — lihat risiko #1). Sesuaikan `extends` di 4 controller.
6. Buat `Modules/LMS/routes/web.php` dari blok §4.
7. Daftarkan route di `Modules/LMS/app/Providers/LMSServiceProvider.php`
   (`loadRoutesFrom` dalam `boot()`); pastikan provider terdaftar
   (nwidart auto-register via `module.json` providers).
8. `composer dump-autoload` — pastikan `Modules\LMS\` ter-autoload.
9. `php artisan route:list --name=app.guru.materi --name=app.siswa.tugas` (dst.) —
   verifikasi 25 route muncul dari modul; **sementara JANGAN hapus route lama**,
   biarkan duplikat terdeteksi dulu (duplikat nama route = sinyal).
10. Hapus 25 baris + 4 import dari `routes/web.php`; `route:list` lagi —
    pastikan tidak ada nama route hilang/ganda.
11. Regenerasi Wayfinder: `php artisan wayfinder:generate` (cek perintah aktual
    versi terinstal) atau `npm run build`; verifikasi file baru muncul di
    `resources/js/actions/Modules/LMS/...` dan file lama di `.../actions/App/...`
    untuk materi/tugas hilang.
12. Perbarui import di 8 file frontend §6 (poin 1–8).
13. `npm run build` + `php artisan test` (atau suite backend yang relevan) —
    target: tidak ada error; buka manual `/app/guru/materi`, `/app/tugas`,
    `/app/guru/tugas/{id}/pengumpulan` sebagai smoke test.
14. Satu commit konsolidasi (aturan user: multi-part = SATU commit), push via
    GitHub API sesuai standing approval.

---

## 8. Risiko / hal yang belum pasti

1. **`BaseAppController` milik siapa.** Keempat controller LMS `extends
   App\Http\Controllers\BaseAppController`. Kalau tetap di core, modul LMS punya
   dependensi ke core selamanya (pragmatis, disarankan fase 1). Kalau modul harus
   mandiri, perlu salinan `BaseLMSController` — tapi isi BaseAppController belum
   diaudit (mungkin ada helper auth/guru yang dipakai CBT juga). **Perlu dibaca
   sebelum eksekusi.**
2. **Composer gate masih terblokir** — seluruh eksekusi menunggu
   `nwidart/laravel-modules` terinstal. Struktur `Modules/`, autoloading
   `Modules\LMS\`, dan ServiceProvider semuanya bergantung padanya.
3. **Wayfinder + modul: perilaku generator belum terverifikasi.** Docblock `@see`
   menunjuk path lama; belum pasti generator memetakan controller modul ke
   `resources/js/actions/Modules/LMS/...` secara otomatis atau butuh konfigurasi
   `wayfinder` di `vite.config.ts`. Kalau path hasil beda, checklist §6 ikut
   bergeser — verifikasi di langkah 11 sebelum edit frontend.
4. **Kopling Tugas→Penilaian/DetailPenilaian** (§2.4): pindah namespace murni aman,
   tapi ini titik di mana batas modul bocor. Jangan refactor jadi event/service di
   fase 1 — risiko regresi nilai.
5. **Repo kotor saat audit** (tim paralel in-flight di `fix-cbt-v2`): eksekutor
   HARUS `git status` & koordinasi sebelum menyentuh `routes/web.php` agar tidak
   menimpa pekerjaan tim lain. Idealnya eksekusi setelah branch bersih/merge.
6. **`app-only` middleware di route modul:** pastikan middleware alias tersedia
   saat ServiceProvider modul boot (urutan boot provider vs `bootstrap/app.php`).
   Blok §4 memakai grup middleware yang sama — bila modul me-load route sebelum
   alias terdaftar, muncul `Target class [app-only] does not exist`. Uji di
   langkah 9.
7. **Duplikat nama route transien** (langkah 9–10): Laravel menimpa diam-diam route
   bernama sama yang didaftar belakangan — urutan load modul vs `routes/web.php`
   menentukan mana yang menang selama masa transisi. Jendela ini harus sesingkat
   mungkin; jangan deploy di antaranya.
