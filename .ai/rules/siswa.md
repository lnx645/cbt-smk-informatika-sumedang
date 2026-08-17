---
paths:
  - 'resources/js/pages/admin/Siswa/**'
---

# Siswa

## Siswa route key uses NISN (string PK)
Siswa uses `nisn` as primary key (string, non-incrementing). Wayfinder generates `{ siswa: string }` route args, so always pass `siswa: item.nisn`, never `item.id`. CrudManager's CrudItem `id` type was widened to `number | string` to support this. The `through()` mapper must include both `id` (nisn value) and `nisn` for CrudManager edit/delete to work.
