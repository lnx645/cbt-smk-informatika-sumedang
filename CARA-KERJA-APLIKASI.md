# Cara Kerja Aplikasi CBT SMK Informatika Sumedang

Aplikasi ini adalah sistem **Computer Based Test (CBT)** berbasis web untuk lingkungan
SMK (Sekolah Menengah Kejuruan), khususnya jurusan informatika. Dibangun dengan
**Laravel 12 + Inertia.js v3 + Svelte 5 + PostgreSQL**, dengan antarmuka admin
menggunakan komponen dari **Sveltestrap**.

---

## 1. Peran Pengguna (Role)

Sistem mengenal tiga peran utama (disimpan pada kolom `role` di tabel `users`):

| Role    | Keterangan                                              |
| ------- | ------------------------------------------------------- |
| `admin` | Mengelola data master (jurusan, kelas, tahun ajaran).   |
| `guru`  | Mengelola materi, tugas, soal ujian, dan nilai.         |
| `siswa` | Mengikuti pembelajaran, tugas, dan ujian.               |

Setiap `user` memiliki relasi one-to-one ke `siswa` (melalui `nisn`) atau `guru`
(melalui `guru_id`).

---

## 2. Struktur Data Master

### Jurusan
- Model: `App\Models\Jurusan` (tabel `jurusans`).
- Memiliki banyak `Kelas` (`hasMany`) dan banyak `Siswa` secara tidak langsung
  melalui kelas (`hasManyThrough` via `Kelas`).

### Kelas
- Model: `App\Models\Kelas` (tabel `kelas`).
- Relasi penting:
  - `jurusan()` → `BelongsTo` ke `Jurusan` (`jurusan_id`).
  - `walikelas()` → `BelongsTo` ke `Guru` (`guru_id`), yaitu guru yang menjadi
    wali kelas.
  - `siswas()` → `hasManyThrough` ke `Siswa` melalui pivot `siswa_kelas`.
  - `parent()` / `children()` → hierarki kelas (mis. kelas induk & sub-kelas).

### Tahun Ajaran
- Model: `App\Models\TahunAjaran` (tabel `tahun_ajaran`).
- Kolom: `name` (contoh: `2024/2025`) dan `active` (boolean).
- **Hanya boleh ada satu tahun ajaran yang aktif.** Saat menyimpan Entri dengan
  `active = true`, controller otomatis menonaktifkan tahun ajaran lain
  (`TahunAjaranController::store/update`).
- Tahun ajaran aktif di-share ke seluruh halaman lewat Inertia middleware
  (`HandleInertiaRequests`) sebagai prop `tahunAjaranAktif`, sehingga bisa
  ditampilkan di sidebar maupun halaman lain.

---

## 3. Siswa & Riwayat Kelas (Penting)

`Siswa` **tidak** memiliki kolom `kelas_id` maupun `jurusan_id` secara langsung.
Hubungan kelas dan jurusan diraih melalui **tabel pivot `siswa_kelas`**:

- Tabel `siswa_kelas` menyimpan:
  - `siswa_nisn` → FK ke `siswa.nisn`
  - `kelas_id` → FK ke `kelas.id`
  - `active` → boolean, menandai kelas yang sedang diikuti siswa tersebut.
- `Siswa::siswaKelas()` → `hasMany` (riwayat seluruh kelas yang pernah diikuti).
- `Siswa::kelas()` → `hasOneThrough` melalui `siswa_kelas` **yang `active = true`**,
  sehingga `siswa.kelas` langsung mengembalikan kelas aktif beserta
  `siswa.kelas.jurusan`.

> Alur ini memungkinkan siswa memiliki **riwayat kelas** (mis. pindah kelas antar
> tahun ajaran) tanpa kehilangan data historis, dan jurusan selalu diambil dari
> kelas aktifnya, bukan disimpan terpisah di siswa.

---

## 4. Alur Tahun Ajaran Aktif

1. Admin membuka menu **Pengaturan → Pengaturan Tahun Ajaran**.
2. Admin membuat tahun ajaran (mis. `2025/2026`) dan mencentang **"Jadikan Aktif"**.
3. Sistem menonaktifkan tahun ajaran lain, lalu menyimpan yang baru sebagai aktif.
4. Middleware Inertia menyuntikkan `tahunAjaranAktif` ke semua halaman.
5. Di sidebar (footer), semua pengguna melihat info
   *"Tahun Ajaran Aktif: <nama>"* (atau peringatan kuning jika belum ada yang aktif).
6. Jika **tidak ada** tahun ajaran aktif, maka pada tampilan siswa akan muncul
   banner informasi: **"Tahun Pelajaran Baru Belum dimulai"**.

---

## 5. Antarmuka Admin (CRUD)

Seluruh fitur CRUD admin menggunakan komponen generik
`resources/js/components/crud/CrudManager.svelte`:

- Mendukung kolom, field form, pencarian, filter, serta field bertipe
  `checkbox` (toggle switch kustom) untuk flag aktif.
- Menggunakan **Wayfinder** agar action controller (`JurusanController`,
  `MatpelController`, `TahunAjaranController`, dll.) terhubung ke route secara
  terjamin tipe (type-safe) dari sisi frontend.
- Setelah tambah/edit berhasil, modal otomatis tertutup (`onSuccess`).

Menu admin (`AdminLayout.svelte`) menggunakan ikon Bootstrap Icons yang sesuai
dengan nama masing-masing menu.

---

## 6. Alur Autentikasi & Layout

- `UserLayout` (area guru/siswa) dan `AdminLayout` (area admin) sama-sama
  membungkus `AppShellLayout` yang menyediakan sidebar, header, dan dropdown user.
- Data user yang sedang login di-share lewat `HandleInertiaRequests` (`auth.user`)
  lengkap dengan relasi `siswa` (termasuk `kelas` & `jurusan`) dan `guru`.
- Proteksi halaman dilakukan lewat middleware Laravel (`auth`, role, dll.).

---

## 7. Ringkasan Relasi

```
Jurusan 1──* Kelas *──1 Guru (walikelas)
                │
                │ 1
                ├──* SiswaKelas (pivot: siswa_nisn, kelas_id, active)
                │            │
                │            │ *──1 Siswa
                │
TahunAjaran (active) ── bagian dari sistem, di-share ke UI via middleware
```

---

## 8. Catatan Pengembangan

- Migrasi berada di `database/migrations/` (urutan penting: `siswa_kelas`
  diperbarui menambah `siswa_nisn`, `kelas_id`, `active`; `kelas_id` dihapus dari
  tabel `siswa`).
- Seeders: `JurusanSeeder`, `GuruSeeder`, `KelasSeeder` (mengaitkan `jurusan_id`),
  `SiswaSeeder` (membuat `SiswaKelas` aktif), `TahunAjaranSeeder`.
- Jalankan `php artisan migrate --seed` untuk mengisi data awal.
- Frontend dibangun ulang dengan `bun run build` / `bun run dev`.
