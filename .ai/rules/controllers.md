---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Only count/list leaf Kelas in statistics and dropdowns
Kelas forms a hierarchy via parent_id (root = tingkat, mid = jurusan, leaf = actual class). In dashboard/statistics counts, per-jurusan class counts, wali-kelas coverage, and `daftar_kelas` dropdowns, use `Kelas::leaf()` (model scope = `whereDoesntHave('children')`) so root & jurusan nodes are excluded. Example: `'kelas' => Kelas::leaf()->count()`, `$jurusan->kelas()->leaf()->count()`.

## Use CASE expression for ordered day names in PostgreSQL
When ordering by a 'hari' (day name) column in PostgreSQL, use a CASE expression instead of MySQL's FIELD() function, which doesn't exist in PostgreSQL. Example: orderByRaw("CASE WHEN hari = 'Senin' THEN 1 ... END").

## Use App\Support\Toast for flash messages
Use Toast::success() / Toast::error() / Toast::warning() / Toast::info() instead of Inertia::flash('toast', [...]). Return Redirect::back() after flashing Toast (not Redirect::route).
