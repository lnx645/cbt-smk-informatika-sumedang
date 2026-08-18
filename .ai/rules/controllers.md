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

## Resolve user via $request->user() in controller methods, not the constructor
Laravel caches controller instances on Route objects, so `$this->user` captured in the base Controller constructor goes stale when the same app handles multiple requests (feature tests with several actingAs calls, long-running processes). Always resolve `$request->user()` (or its guru/siswa relations) inside each method instead of relying on the constructor-injected user. Also: `Storage::download()` returns StreamedResponse, not BinaryFileResponse.

## Jangan pakai static cache di controller
Jangan memoize query per-request dengan properti static di controller (mis. TahunAjaran aktif) — state static bertahan antar test dalam satu proses Pest dan merusak isolasi test (nilai dari test pertama bocor ke test berikutnya). Gunakan memoization per-instance (??=), atau Cache::remember dengan invalidation eksplisit jika perlu lintas request.
