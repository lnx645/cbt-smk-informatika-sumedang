---
paths:
  - 'resources/scss/components/*.scss'
---

# Components

## Warna teks button harus eksplisit, jangan andalkan color-contrast()
Bootstrap button-variant() memilih hitam/putih via color-contrast(). Karena primary resmi #0091D4 terang, hasilnya teks hitam di atas biru. Selalu passing $color: $white (atau gelap untuk warning/light) eksplisit + hover/active/disabled. Teks pada latar putih (outline, link, nav aktif) wajib shade 700 untuk ≥4.5:1. Warna resmi Tut Wuri: biru #0091D4, emas #FDD406, putih #FEFEFE — jangan hardcode hex, pakai token ($primary, $primary-700, dll).
