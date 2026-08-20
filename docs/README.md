# Dokumentasi Aplikasi E-Learning SMK IFSU (CBT)

Indeks seluruh dokumentasi teknis untuk bahan laporan KP & pembelajaran.

## Bahan UML

| Dokumen | Isi | Untuk apa |
|---|---|---|
| [UML-APLIKASI.md](./UML-APLIKASI.md) | UML seluruh aplikasi: use case, activity, class, sequence, state, ERD, arsitektur & tech stack, struktur proyek | Gambar diagram umum aplikasi |
| [UML-NAIK-KELAS.md](./UML-NAIK-KELAS.md) | Bahan UML khusus fitur naik kelas (use case, activity, class, sequence, state, ERD) | Diagram fitur naik kelas |
| [REQUIREMENTS-NAIK-KELAS.md](./REQUIREMENTS-NAIK-KELAS.md) | Requirement, logika detail, catatan perbaikan (bug data root XI) naik kelas | Narasi & pembahasan fitur |
| [REQUIREMENTS-PENILAIAN.md](./REQUIREMENTS-PENILAIAN.md) | Requirement & alur fitur penilaian siswa | Narasi & pembahasan fitur penilaian |

## Dokumen UML Lengkap (notasi Mermaid, render di GitHub/VS Code)

| Dokumen | Isi |
|---|---|
| [uml/00-index.md](./uml/00-index.md) | Indeks & profil aplikasi |
| [uml/01-use-case.md](./uml/01-use-case.md) | Use case per aktor (Tamu, Admin, Guru, Siswa) + tabel 23 UC |
| [uml/02-class.md](./uml/02-class.md) | Class diagram 14 model + relasi + controller |
| [uml/03-sequence.md](./uml/03-sequence.md) | Sequence diagram 13 alur utama |
| [uml/04-activity.md](./uml/04-activity.md) | Activity diagram 8 proses bisnis |
| [uml/05-state.md](./uml/05-state.md) | State machine diagram 9 objek berstatus |
| [uml/06-erd.md](./uml/06-erd.md) | ERD seluruh tabel PostgreSQL |
| [uml/07-component.md](./uml/07-component.md) | Component diagram arsitektur |
| [uml/08-deployment.md](./uml/08-deployment.md) | Deployment diagram produksi & dev |

## Aplikasi Diagram Interaktif (BPMN + Fishbone)

Proyek Svelte + Vite di luar repo: **`D:\rancangan`** (Vite 7, Svelte 5,
bpmn-js 18.25.0 via bundler + diagram-js 15.1.0).

- 8 diagram BPMN 2.0 interaktif (bpmn-js Modeler): autentikasi, master data,
  siswa & akun, guru & penugasan, materi, tugas & pengumpulan, penilaian,
  naik kelas — dapat zoom, fit, dan unduh SVG/BPMN.
- 1 fishbone (Ishikawa) analisis akar masalah dengan pendekatan 6M.
- Dokumen UML (`uml/*.md`) disalin ke `public/uml/` dan dapat dibuka dari
  aplikasi.

Jalankan:

```bash
cd D:\rancangan
bun install     # sekali
bun run dev     # mode pengembangan (hot reload)
bun run build   # hasil statis di dist/
bun run preview # pratinjau hasil build
```

## Cara membaca

1. Mulai dari **UML-APLIKASI.md §1** (gambaran umum & tech stack) untuk
   memahami konteks.
2. Lanjut ke **uml/00-index.md** → **01–08** untuk diagram lengkap seluruh
   aplikasi (Mermaid).
3. Untuk diagram interaktif & ekspor SVG/BPMN, buka aplikasi `D:\rancangan`
   (`bun run dev` lalu akses URL yang ditampilkan Vite).
4. Untuk fitur tertentu (naik kelas, penilaian) buka dokumen spesifiknya.

## Catatan

- Dokumen UML berupa notasi Mermaid (bisa dirender GitHub/VS Code) plus tabel
  elemen siap transkrip ke StarUML/draw.io/Visio.
- Detail logika & catatan bug ada di dokumen REQUIREMENTS-*.