---
paths:
  - 'resources/scss/components/*.scss'
---

# Components

## Warna teks button harus eksplisit, jangan andalkan color-contrast()
Bootstrap button-variant() memilih hitam/putih via color-contrast(). Karena primary #4182b3 gelap, teks putih aman. Selalu passing $color: $white (atau gelap untuk warning/light) eksplisit + hover/active/disabled. Teks pada latar putih (outline, link, nav aktif) wajib shade 700 untuk ≥4.5:1. Warna brand: biru #4182b3 ($primary / $blue-500), biru gelap #2b567a ($primary-700), putih #FEFEFE — jangan hardcode hex, pakai token ($primary, $primary-700, dll).
